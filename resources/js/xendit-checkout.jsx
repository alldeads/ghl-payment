import React, { useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import axios from 'axios';

function CheckoutApp() {
    const [mode, setMode] = useState('card');
    const [amount, setAmount] = useState('');

    const [cardNumber, setCardNumber] = useState('');
    const [cardExpMonth, setCardExpMonth] = useState('');
    const [cardExpYear, setCardExpYear] = useState('');
    const [cardCvn, setCardCvn] = useState('');
    const [saveCard, setSaveCard] = useState(false);

    const [isCardLoading, setIsCardLoading] = useState(false);
    const [isEwalletLoading, setIsEwalletLoading] = useState(false);
    const [isQrLoading, setIsQrLoading] = useState(false);
    const [isQrSimLoading, setIsQrSimLoading] = useState(false);

    const [errorMessage, setErrorMessage] = useState('');
    const [apiResponse, setApiResponse] = useState('');

    const [otpUrl, setOtpUrl] = useState('');

    const [qrCodeId, setQrCodeId] = useState('');
    const [qrImageUrl, setQrImageUrl] = useState('');
    const [qrString, setQrString] = useState('');
    const [qrAmount, setQrAmount] = useState('');
    const [qrMerchant, setQrMerchant] = useState('');

    const isOtpVisible = otpUrl !== '';

    const modeButtonClass = (value) => {
        const base = 'flex-1 cursor-pointer p-4 text-center text-black transition';
        if (mode === value) {
            return `${base} bg-white font-bold`;
        }

        return `${base} bg-gray-200`;
    };

    const clearError = () => setErrorMessage('');

    const setResponse = (value) => {
        try {
            setApiResponse(JSON.stringify(value, null, 2));
        } catch {
            setApiResponse(String(value));
        }
    };

    const normalizedAmount = useMemo(() => Number(amount || 0), [amount]);

    const xendit = typeof window !== 'undefined' ? window.Xendit : undefined;

    const publishableKey = window.__XENDIT_CONFIG__?.publishableKey || '';
    if (xendit && publishableKey && !window.__XENDIT_KEY_SET__) {
        xendit.setPublishableKey(publishableKey);
        window.__XENDIT_KEY_SET__ = true;
    }

    const handleChargeCard = (event) => {
        event.preventDefault();
        clearError();

        if (!xendit || !xendit.card) {
            setErrorMessage('Xendit JS SDK is not loaded yet.');
            return;
        }

        if (!cardCvn) {
            setErrorMessage('Card CVV/CVN is optional when creating card token, but highly recommended to include it.');
            return;
        }

        if (normalizedAmount < 20) {
            setErrorMessage('The amount must be at least 20.');
            return;
        }

        if (!xendit.card.validateCardNumber(cardNumber)) {
            setErrorMessage('Invalid card number.');
            return;
        }

        if (!xendit.card.validateExpiry(cardExpMonth, cardExpYear)) {
            setErrorMessage('Invalid card expiry date.');
            return;
        }

        if (!xendit.card.validateCvn(cardCvn)) {
            setErrorMessage('Invalid card CVV/CVN.');
            return;
        }

        setIsCardLoading(true);

        xendit.card.createToken(
            {
                amount: normalizedAmount,
                card_number: cardNumber,
                card_exp_month: cardExpMonth,
                card_exp_year: cardExpYear,
                card_cvn: cardCvn,
                is_multiple_use: saveCard,
                should_authenticate: true,
            },
            (tokenErr, creditCardToken) => {
                if (tokenErr) {
                    setErrorMessage(tokenErr.message || 'Card tokenization failed.');
                    setIsCardLoading(false);
                    return;
                }

                xendit.card.createAuthentication(
                    {
                        amount: normalizedAmount,
                        token_id: creditCardToken.id,
                    },
                    async (authErr, authResponse) => {
                        if (authErr && Object.keys(authErr).length > 0) {
                            setErrorMessage(authErr.message || 'Authentication failed.');
                            setIsCardLoading(false);
                            return;
                        }

                        if (!authResponse) {
                            setErrorMessage('Missing authentication response.');
                            setIsCardLoading(false);
                            return;
                        }

                        if (authResponse.status === 'IN_REVIEW') {
                            setOtpUrl(authResponse.payer_authentication_url || '');
                            setIsCardLoading(false);
                            return;
                        }

                        if (authResponse.status === 'FAILED') {
                            setResponse(authResponse);
                            setErrorMessage(authResponse.failure_reason || authResponse.status || 'Authentication failed.');
                            setIsCardLoading(false);
                            return;
                        }

                        try {
                            const charge = await axios.post('/pay-with-card', {
                                amount: normalizedAmount,
                                token_id: authResponse.credit_card_token_id,
                                authentication_id: authResponse.id,
                            });

                            setResponse(charge.data);
                            setOtpUrl('');
                        } catch (error) {
                            const message =
                                error?.response?.data?.message ||
                                'Charge card failed.';
                            setErrorMessage(message);
                        } finally {
                            setIsCardLoading(false);
                        }
                    }
                );
            }
        );
    };

    const handleChargeEwallet = async (event) => {
        event.preventDefault();
        clearError();

        if (normalizedAmount < 1) {
            setErrorMessage('The amount must be at least 1.');
            return;
        }

        setIsEwalletLoading(true);

        try {
            const response = await axios.post('/pay-via-ewallet', {
                amount: normalizedAmount
            });

            setResponse(response.data);

            const checkoutUrl = response?.data?.actions?.desktop_web_checkout_url;
            if (checkoutUrl) {
                window.location.href = checkoutUrl;
            }
        } catch (error) {
            const message = error?.response?.data?.message || 'eWallet charge failed.';
            setErrorMessage(message);
        } finally {
            setIsEwalletLoading(false);
        }
    };

    const handleGenerateQr = async (event) => {
        event.preventDefault();
        clearError();

        if (normalizedAmount < 1) {
            setErrorMessage('The amount must be at least 1.');
            return;
        }

        setIsQrLoading(true);

        try {
            const response = await axios.post('/pay-via-qr', {
                amount: normalizedAmount,
                currency: 'IDR',
                type: 'DYNAMIC',
            });

            const payload = response.data?.qr_code || response.data;
            const value = payload?.qr_string || payload?.qr_code_string || payload?.qr_content || '';

            setQrCodeId(payload?.id || '');
            setQrString(value);
            setQrAmount(String(payload?.amount || normalizedAmount));
            setQrMerchant(payload?.business_name || payload?.merchant_name || payload?.reference_id || 'Xendit Merchant');
            setQrImageUrl(value ? `https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=${encodeURIComponent(value)}` : '');
            setResponse(response.data);
        } catch (error) {
            const message = error?.response?.data?.message || 'QR creation failed.';
            setErrorMessage(message);
        } finally {
            setIsQrLoading(false);
        }
    };

    const handleSimulateQr = async (event) => {
        event.preventDefault();
        clearError();

        if (!qrCodeId) {
            setErrorMessage('Generate QR first before simulation.');
            return;
        }

        setIsQrSimLoading(true);

        try {
            const response = await axios.post('/pay-via-qr/simulate', {
                qr_code_id: qrCodeId,
            });

            setResponse(response.data);
        } catch (error) {
            const message = error?.response?.data?.message || 'QR simulation failed.';
            setErrorMessage(message);
        } finally {
            setIsQrSimLoading(false);
        }
    };

    return (
        <>
            {isOtpVisible ? (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-8">
                    <div className="h-[80vh] w-full max-w-4xl rounded-xl bg-white p-4 shadow-2xl">
                        <iframe title="payer-auth" src={otpUrl} className="h-full w-full rounded-md" />
                    </div>
                </div>
            ) : null}

            <div className="mx-auto mt-8 flex max-w-3xl flex-col items-center gap-4 px-4">
                <header className="text-sm">
                    <h1 className="mb-2 text-xl font-bold">Xendit Checkout Test</h1>
                    <p className="flex gap-3">
                        <a
                            href="https://docs.xendit.co/credit-cards/integrations/test-scenarios"
                            className="border-b border-blue-600 text-blue-600"
                            target="_blank"
                            rel="noreferrer"
                        >
                            Test card numbers
                        </a>
                        <a
                            href="https://docs.xendit.co/credit-cards/integrations/test-scenarios#simulating-failed-charge-transactions"
                            className="border-b border-blue-600 text-blue-600"
                            target="_blank"
                            rel="noreferrer"
                        >
                            Test failed scenarios
                        </a>
                    </p>
                </header>

                <div className="w-full rounded-md border border-gray-300 bg-white shadow-sm">
                    <div className="flex border-b border-gray-300 text-sm">
                        <button className={modeButtonClass('card')} onClick={() => setMode('card')} type="button">Credit/Debit Card</button>
                        <button className={modeButtonClass('ewallet')} onClick={() => setMode('ewallet')} type="button">E-Wallet</button>
                        <button className={modeButtonClass('qr')} onClick={() => setMode('qr')} type="button">QR Payment</button>
                    </div>

                    <div className="p-6">
                        <input
                            value={amount}
                            onChange={(event) => setAmount(event.target.value)}
                            placeholder="Amount to pay"
                            type="number"
                            className="mb-4 w-full rounded-md border border-gray-300 p-3"
                        />

                        {mode === 'card' ? (
                            <div className="space-y-3">
                                <input value={cardNumber} onChange={(event) => setCardNumber(event.target.value)} placeholder="Card number" className="w-full rounded-md border border-gray-300 p-3" />
                                <div className="grid grid-cols-3 gap-3">
                                    <input value={cardExpMonth} onChange={(event) => setCardExpMonth(event.target.value)} placeholder="MM" maxLength={2} className="rounded-md border border-gray-300 p-3" />
                                    <input value={cardExpYear} onChange={(event) => setCardExpYear(event.target.value)} placeholder="YYYY" maxLength={4} className="rounded-md border border-gray-300 p-3" />
                                    <input value={cardCvn} onChange={(event) => setCardCvn(event.target.value)} placeholder="CVV" maxLength={4} className="rounded-md border border-gray-300 p-3" />
                                </div>
                                <label className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" checked={saveCard} onChange={(event) => setSaveCard(event.target.checked)} />
                                    Save my information for faster checkout
                                </label>
                                <button type="button" onClick={handleChargeCard} disabled={isCardLoading} className="w-full rounded-md bg-black py-3 text-sm font-bold uppercase text-white disabled:opacity-50">
                                    {isCardLoading ? 'Processing...' : 'Charge Card'}
                                </button>
                            </div>
                        ) : null}

                        {mode === 'ewallet' ? (
                            <button type="button" onClick={handleChargeEwallet} disabled={isEwalletLoading} className="w-full rounded-md bg-black py-3 text-sm font-bold uppercase text-white disabled:opacity-50">
                                {isEwalletLoading ? 'Processing...' : 'Charge with eWallet'}
                            </button>
                        ) : null}

                        {mode === 'qr' ? (
                            <div className="space-y-4">
                                <div className="rounded-md border border-gray-300 bg-gray-100 p-4 text-sm">
                                    <h3 className="mb-2 font-bold">How to pay</h3>
                                    <ol className="list-decimal space-y-1 pl-5">
                                        <li>Select QRPh as your payment method on this checkout page.</li>
                                        <li>A QR code will be displayed with transaction details (amount and merchant).</li>
                                        <li>Open your mobile banking app or e-wallet that supports QRPh.</li>
                                        <li>Use Scan QR Code in your app and scan the QR code shown here.</li>
                                        <li>Verify the amount and recipient, then confirm payment.</li>
                                        <li>Enter your PIN or use biometric authentication if required.</li>
                                    </ol>
                                </div>

                                <div className="flex gap-3">
                                    <button type="button" onClick={handleGenerateQr} disabled={isQrLoading} className="flex-1 rounded-md bg-black py-3 text-sm font-bold uppercase text-white disabled:opacity-50">
                                        {isQrLoading ? 'Generating...' : 'Generate QRPh'}
                                    </button>
                                    <button type="button" onClick={handleSimulateQr} disabled={isQrSimLoading || !qrCodeId} className="flex-1 rounded-md bg-gray-800 py-3 text-sm font-bold uppercase text-white disabled:opacity-50">
                                        {isQrSimLoading ? 'Simulating...' : 'Simulate QR Payment'}
                                    </button>
                                </div>

                                {qrImageUrl ? (
                                    <div className="rounded-md border border-gray-300 p-4">
                                        <div className="mb-3 rounded-md border border-gray-300 bg-gray-100 p-3 text-sm">
                                            <p><strong>Amount:</strong> {qrAmount}</p>
                                            <p><strong>Merchant:</strong> {qrMerchant}</p>
                                            <p><strong>QR ID:</strong> {qrCodeId}</p>
                                        </div>
                                        <img src={qrImageUrl} alt="QR Code" className="mx-auto h-56 w-56" />
                                        <pre className="mt-3 overflow-auto rounded-md bg-gray-100 p-3 text-xs whitespace-pre-wrap">{qrString}</pre>
                                    </div>
                                ) : null}
                            </div>
                        ) : null}

                        {errorMessage ? (
                            <div className="mt-4 rounded-md bg-red-100 p-3 text-sm text-red-700">{errorMessage}</div>
                        ) : null}
                    </div>
                </div>

                {apiResponse ? (
                    <div className="w-full rounded-md border border-gray-300 bg-white p-6 shadow-sm">
                        <h2 className="mb-2 text-lg font-bold">Xendit API Response</h2>
                        <pre className="overflow-auto whitespace-pre-wrap rounded-md bg-gray-100 p-3 text-xs">{apiResponse}</pre>
                    </div>
                ) : null}
            </div>
        </>
    );
}

const rootElement = document.getElementById('xendit-checkout-root');
if (rootElement) {
    createRoot(rootElement).render(<CheckoutApp />);
}
