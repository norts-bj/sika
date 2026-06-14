// jQuery(document).ready(function($) {

(() => {
  /**
   * Objet de la passerelle Fedapay pour le Form Runner de GiveWP v3
   */
  const SikaFedapayGateway = {
    // ⚠️ CRUCIAL : Doit correspondre EXACTEMENT à l'ID retourné par votre classe PHP (sika-fedapay)
    id: "sika-fedapay-gateway",

    initialize() {
      // S'exécute au chargement du formulaire
      console.log("Sika Fedapay Gateway v3 chargée avec succès.");
    },

    /**
     * Cette méthode s'exécute lorsque le donateur clique sur "Faire un don".
     * Elle DOIT retourner une Promesse qui ne se résout que si le paiement réussit.
     */
    // async beforeCreatePayment(data) {
    //   return new Promise((resolve, reject) => {
    //     try {
    //       console.log("data: ", data)
    //       // 1. Récupération des informations du formulaire Next-Gen (React DOM)
    //       // Les sélecteurs v3 ciblent les éléments générés par le moteur React
    //       const totalElement = getAmountSafe();
    //       // const totalElement = document.querySelector('.givewp-form-summary__total-amount') ||
    //       //                       document.querySelector('[data-testid="total-amount"]');

    //       if (!totalElement) {
    //         throw new Error("Impossible de récupérer le montant du don depuis le formulaire.");
    //       }

    //       // Nettoyage du montant (conversion du texte en nombre)
    //       const amount = parseFloat(totalElement.replace(/[^0-9.,]/g, '').replace(',', '.'));

    //       // Récupération optionnelle de l'email s'il est déjà rempli
    //       const emailInput = document.querySelector('input[type="email"]');
    //       const email = emailInput ? emailInput.value : '';

    //       // 2. Récupération des clés configurées en PHP via fedapaySettings
    //       const { publicKey, theme, isSandbox, position, paymentmethod } = fedapaySettings;

    //       if (!publicKey) {
    //         throw new Error("La clé publique Fedapay est manquante dans la configuration.");
    //       }

    //       // 3. OUVERTURE DU WIDGET KKIAPAY
    //       openFedapayWidget({
    //         amount: amount,
    //         position: position || 'center',
    //         theme: theme || '#000000',
    //         sandbox: parseInt(isSandbox) === 1,
    //         key: publicKey,
    //         email: email,
    //         name: "Donateur Sika",
    //         paymentmethod: paymentmethod || 'all',
    //         reason: "Donation via GiveWP",
    //         sdk: "give"
    //       });

    //       // 4. ÉCOUTE DU SUCCÈS DE LA TRANSACTION
    //       addSuccessListener((response) => {
    //         // On résout la promesse en renvoyant l'ID de transaction.
    //         // Cette clé 'fedapayTransactionId' arrivera directement dans le tableau $gatewayData du PHP
    //         resolve({
    //           fedapayTransactionId: response.transactionId
    //         });
    //       });

    //       // 5. GESTION DE LA FERMETURE (ANNULATION)
    //       if (typeof addCloseListener === 'function') {
    //         addCloseListener(() => {
    //           reject(new Error("Le paiement a été annulé par l'utilisateur."));
    //         });
    //       }

    //     } catch (error) {
    //       console.error("Erreur Sika Fedapay Frontend :", error.message);
    //       alert(error.message);
    //       reject(error);
    //     }
    //   });
    // },

    components: {},
    actionHandlers: {},
    validators: {},

    async beforeCreatePayment(data) {
      const { amount, currency, email, firstName, lastName } = data;
      const { publicKey, isSandbox } = fedapaySettings;

      return new Promise((resolve, reject) => {
        const widget = FedaPay.init({
          public_key: publicKey,
          sandbox: isSandbox,
          transaction: {
            amount: amount,
            description: `Don de ${amount} ${currency}`,
          },
          customer: {
            email: email,
            lastname: lastName,
            firstname: firstName,
          },
          onComplete: function (response) {
            console.log("Fedapay response: ", response);
            if (response.reason === FedaPay.CHECKOUT_COMPLETED) {
              resolve({
                fedapayTransactionId: response.transaction.id,
              });
            } else {
              reject(new Error("Paiement annulé ou échoué."));
            }
          },
        });

        widget.open();
      });
    },

    /**
     * Rendu des champs spécifiques (optionnel)
     * Fedapay utilise un widget modal externe, on n'a donc pas besoin de champs React ici.
     */
    Fields() {
      return null;
    },
  };

  /**
   * Enregistrement de la passerelle auprès de l'écosystème GiveWP v3
   */
  if (window.givewp && window.givewp.gateways) {
    window.givewp.gateways.register(SikaFedapayGateway);
  } else {
    // Sécurité au cas où le script se chargerait trop tôt
    document.addEventListener("givewp_init", () => {
      window.givewp.gateways.register(SikaFedapayGateway);
    });
  }
})();
// });
