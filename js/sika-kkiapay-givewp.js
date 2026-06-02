(() => {
  const SikaKkiapayGateway = {
    id: "sika-kkiapay-gateway",

    initialize() {
      console.log("Sika Kkiapay Gateway v3 chargée.");
    },

    async beforeCreatePayment(data) {
      console.log("data:", data);
    console.log("sikaKkiapaySettings:", sikaKkiapaySettings);
    console.log("openKkiapayWidget:", typeof openKkiapayWidget);

      const { amount, currency, email, firstName, lastName } = data;
      const { publicKey, isSandbox, position, theme, paymentmethod } =
        sikaKkiapaySettings;

      const initWidgetComponent = {
          amount: amount,
          position: position || "center",
          theme: theme || "#4661b9",
          sandbox: true, // isSandbox,
          key: publicKey,
          email: email,
          name: `${firstName} ${lastName}`,
          // paymentmethod: paymentmethod || "all",
          reason: `Don de ${amount} ${currency}`,
        };
        console.log("initWidgetComponent:", initWidgetComponent);
      return new Promise((resolve, reject) => {
        openKkiapayWidget(initWidgetComponent);

        addKkiapayListener("success", (response) => {
          resolve({
            kkiapayTransactionId: response.transactionId,
          });
        });

        addKkiapayCloseListener(() => {
          reject(new Error("Paiement annulé."));
        });
      });
    },

    Fields() {
      return null;
    },
  };

  if (window.givewp && window.givewp.gateways) {
    window.givewp.gateways.register(SikaKkiapayGateway);
  } else {
    document.addEventListener("givewp_init", () => {
      window.givewp.gateways.register(SikaKkiapayGateway);
    });
  }
})();
