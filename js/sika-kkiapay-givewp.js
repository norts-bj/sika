// jQuery(document).ready(function($) {

(() => {
  /**
   * Objet de la passerelle Kkiapay pour le Form Runner de GiveWP v3
   */
  const SikaKkiapayGateway = {
    // ⚠️ CRUCIAL : Doit correspondre EXACTEMENT à l'ID retourné par votre classe PHP (sika-kkiapay)
    id: "sika-kkiapay-gateway",

    initialize() {
      // S'exécute au chargement du formulaire
      console.log("Sika Kkiapay Gateway v3 chargée avec succès.");
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

    //       // 2. Récupération des clés configurées en PHP via kkiapaySettings
    //       const { publicKey, theme, isSandbox, position, paymentmethod } = kkiapaySettings;

    //       if (!publicKey) {
    //         throw new Error("La clé publique Kkiapay est manquante dans la configuration.");
    //       }

    //       // 3. OUVERTURE DU WIDGET KKIAPAY
    //       openKkiapayWidget({
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
    //         // Cette clé 'kkiapayTransactionId' arrivera directement dans le tableau $gatewayData du PHP
    //         resolve({
    //           kkiapayTransactionId: response.transactionId
    //         });
    //       });

    //       // 5. GESTION DE LA FERMETURE (ANNULATION)
    //       if (typeof addCloseListener === 'function') {
    //         addCloseListener(() => {
    //           reject(new Error("Le paiement a été annulé par l'utilisateur."));
    //         });
    //       }

    //     } catch (error) {
    //       console.error("Erreur Sika Kkiapay Frontend :", error.message);
    //       alert(error.message);
    //       reject(error);
    //     }
    //   });
    // },

    components: {},
    actionHandlers: {},
    validators: {},

    async beforeCreatePayment(data) {
      const { amount, currency, email, firstName, lastName, gatewayId } = data;

      if (!amount) {
        throw new Error("Amount manquant");
      }
      const { publicKey, theme, isSandbox, position, paymentmethod } =
        kkiapaySettings;


      // const kkiapayInstance = window.Kkiapay(publicKey, {
      //       sandbox: parseInt(isSandbox) === 1,
      //       theme: theme || '#000000',
      //       position: position || 'center',
      //       sdk: "give"
      //     });

      //     // 6. ATTACHE DES ÉCOUTEURS D'ÉVÉNEMENTS (Standard v3)
      //     // Événement de succès
      //     kkiapayInstance.on("success", (response) => {
      //       console.log("Kkiapay v3 - Paiement réussi :", response);
      //       resolve({
      //         kkiapayTransactionId: response.transactionId
      //       });
      //     });

      //     // Événement de fermeture / annulation
      //     kkiapayInstance.on("close", () => {
      //       console.log("Kkiapay v3 - Widget fermé par l'utilisateur.");
      //       reject(new Error("Le paiement a été annulé par l'utilisateur."));
      //     });

      try {
        openKkiapayWidget({
          amount: Number(amount),
          // position: position || 'center',
          // theme: theme || '#000000',
          // sandbox: parseInt(isSandbox) === 1,
          sandbox: true,
          key: publicKey,
          // currency,
          // email,
          // paymentmethod: paymentmethod || 'all',
          // name: `${firstName || ""} ${lastName || ""}`.trim(),
          // reason: "Donation via GiveWP",
          // sdk: "give",
        });
      } catch (error) {
        console.error("Erreur Sika Kkiapay Frontend :", error.message);
        alert(error.message);
      }

      addSuccessListener((res) => {
        return {
          kkiapayTransactionId: res.transactionId,
        };
      });
    },
    /**
     * Rendu des champs spécifiques (optionnel)
     * Kkiapay utilise un widget modal externe, on n'a donc pas besoin de champs React ici.
     */
    Fields() {
      return null;
    },
  };

  /**
   * Enregistrement de la passerelle auprès de l'écosystème GiveWP v3
   */
  if (window.givewp && window.givewp.gateways) {
    window.givewp.gateways.register(SikaKkiapayGateway);
  } else {
    // Sécurité au cas où le script se chargerait trop tôt
    document.addEventListener("givewp_init", () => {
      window.givewp.gateways.register(SikaKkiapayGateway);
    });
  }
})();
// });
