import { loadStripe } from '@stripe/stripe-js';

const LOAD_ERROR = 'Le module de paiement n’a pas pu se charger. Rechargez la page ou réessayez dans quelques instants.';

/**
 * Paiement intégré (Stripe Payment Element) des pages de paiement : rendez-vous,
 * livre et formations. Ne s'active que si le conteneur #payment-form est
 * présent ; configuration et appearance (charte teal) lues depuis ses
 * attributs data-*.
 */
async function initStripePayment() {
    const form = document.getElementById('payment-form');
    if (!form) return;

    const { stripeKey, clientSecret, returnUrl, amountLabel } = form.dataset;
    if (!stripeKey || !clientSecret) return;

    const errorBox = document.getElementById('payment-error');

    const showError = (message) => {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    };

    let stripe;
    try {
        stripe = await loadStripe(stripeKey);
    } catch (error) {
        console.error(error);
    }

    if (!stripe) {
        document.getElementById('payment-skeleton')?.remove();
        showError(LOAD_ERROR);
        return;
    }

    const appearance = {
        theme: 'flat',
        variables: {
            colorPrimary: '#0f766e',
            colorText: '#1f2937',
            colorTextSecondary: '#6b7280',
            colorBackground: '#ffffff',
            colorDanger: '#be123c',
            borderRadius: '14px',
            fontFamily: '"Instrument Sans", system-ui, sans-serif',
            fontSizeBase: '15px',
            spacingUnit: '4px',
            spacingGridRow: '18px',
            focusBoxShadow: '0 0 0 2px rgba(15, 118, 110, 0.25)',
            focusOutline: 'none',
        },
        rules: {
            '.Input': {
                border: '1px solid rgba(15, 23, 42, 0.1)',
                boxShadow: 'none',
                padding: '12px',
            },
            '.Input:focus': {
                border: '1px solid #0f766e',
                boxShadow: '0 0 0 2px rgba(15, 118, 110, 0.25)',
            },
            '.Label': {
                fontWeight: '500',
                marginBottom: '6px',
            },
            '.Tab': {
                border: '1px solid rgba(15, 23, 42, 0.1)',
                boxShadow: 'none',
            },
            '.Tab:hover': {
                border: '1px solid rgba(15, 118, 110, 0.4)',
            },
            '.Tab:focus': {
                boxShadow: '0 0 0 2px rgba(15, 118, 110, 0.25)',
            },
            '.Tab--selected': {
                border: '1px solid #0f766e',
                backgroundColor: '#0f766e',
                color: '#ffffff',
            },
            '.Tab--selected:focus': {
                border: '1px solid #0f766e',
                boxShadow: '0 0 0 2px rgba(15, 118, 110, 0.25)',
            },
            '.TabIcon--selected': {
                fill: '#ffffff',
            },
            '.TabLabel--selected': {
                color: '#ffffff',
            },
        },
    };

    const elements = stripe.elements({ clientSecret, appearance });
    const paymentElement = elements.create('payment', { layout: 'tabs' });
    paymentElement.mount('#payment-element');

    paymentElement.on('ready', () => {
        document.getElementById('payment-skeleton')?.remove();
    });

    paymentElement.on('loaderror', () => {
        document.getElementById('payment-skeleton')?.remove();
        showError(LOAD_ERROR);
    });

    const submitButton = document.getElementById('payment-submit');
    const buttonLabel = document.getElementById('payment-submit-label');
    const buttonSpinner = document.getElementById('payment-submit-spinner');

    const setLoading = (loading) => {
        submitButton.disabled = loading;
        buttonSpinner?.classList.toggle('hidden', !loading);
        if (buttonLabel) {
            const defaultLabel = amountLabel ? `Payer ${amountLabel}` : 'Payer';
            buttonLabel.textContent = loading ? 'Paiement en cours…' : defaultLabel;
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox?.classList.add('hidden');
        setLoading(true);

        try {
            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: { return_url: returnUrl },
            });

            if (error) {
                showError(error.message || 'Le paiement a échoué. Vérifiez vos informations et réessayez.');
                setLoading(false);
            }
        } catch (error) {
            console.error(error);
            showError('Le paiement n’a pas pu être envoyé. Vérifiez votre connexion et réessayez.');
            setLoading(false);
        }
    });
}

initStripePayment();
