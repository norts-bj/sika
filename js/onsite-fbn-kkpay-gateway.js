(() => {
  /**
   * API de notre passerelle
   */
  const fbnKkpayGatewayApi = {
    clientKey: "",
    secureData: "",
    async submit() {


      const selectedGateway = form_element.querySelector(
        ".give-gateway:checked"
      ).value;

      if ("kkiapay" === selectedGateway && submitWithSucess == false) {
        evt.stopImmediatePropagation();
        evt.preventDefault();
        let amount = document
          .querySelector(".give-final-total-amount")
          .getAttribute("data-total");
        let firstname = document.getElementsByName("give_first")[0].value;
        let lastname = document.getElementsByName("give_last")[0].value;
        let email = document.getElementsByName("give_email")[0].value;
        let { key, theme, sandbox, position, paymentmethod } =
          give_kkiapay_vars;

        openKkiapayWidget({
          amount: parseInt(amount),
          name: `${firstname} ${lastname}`,
          email,
          key,
          sandbox,
          theme,
          position,
          paymentmethod: [paymentmethod],
          reason: "donation",
          sdk: "give",
        });
      } else if (onsubmit) {
        onsubmit.call(this, evt);
      }



      console.log(
        this.clientKey,
        this.position,
        this.paymentmethod,
        this.theme,
        this.key,
        this.sandbox,
      );
      if (!this.clientKey) {
        return {
          error: "FbnKkpayGatewayApi clientKey is required.",
        };
      }
      // if (this.secureData.length === 0) {
      //   return {
      //     error: "FbnKkpayGatewayApi data is required.",
      //   };
      // }
      return {
        transactionId: `fbn_transaction-${Date.now()}`,
      };
    },
  };

  /**
   * Champs du formulaire pour notre passerelle
   */
  function FbnKkpayGatewayFields() {
    // const el = window.wp.element.createElement;

    return;
    // el(
    //   "div",{},
    //   el(
    //     "label",
    //     {
    //       htmlFor: "fbn-kkpay-gateway-id",
    //       style: { display: "block", border: "none" },
    //     },
    //     "FBN KKPay Gateway Label",
    //     el("div", {},
    //       el("input", { type: "text", name: "firstname" }),
    //       el("input", { type: "text", name: "lastname" }),
    //       el("input", { type: "email", name: "email" })
    //     )
    //   )
    // );
  }

  /*
  
  evt.stopImmediatePropagation();
        evt.preventDefault();
        let amount = document
          .querySelector(".give-final-total-amount")
          .getAttribute("data-total");
        let firstname = document.getElementsByName("give_first")[0].value;
        let lastname = document.getElementsByName("give_last")[0].value;
        let email = document.getElementsByName("give_email")[0].value;
        let { key, theme, sandbox, position, paymentmethod } =
          give_kkiapay_vars;

        openKkiapayWidget({
          amount: parseInt(amount),
          name: `${firstname} ${lastname}`,
          email,
          key,
          sandbox,
          theme,
          position,
          paymentmethod: [paymentmethod],
          reason: "donation",
          sdk: "give",
          pubkey : e04e1ec0994311f08b5c87d693475c56
          prvtekey: tpk_e04e45d0994311f08b5c87d693475c56
          scrte: tsk_e04e45d1994311f08b5c87d693475c56
  */

  /**
   * Objet principal de la passerelle frontend
   */
  const FbnKkpayGateway = {
    // L'ID DOIT correspondre à l'ID de votre classe PHP
    id: "onsite_fbn_kkpay_gateway",

    initialize() {
      console.log(this.settings);
      const { clientKey } = this.settings;
      fbnKkpayGatewayApi.clientKey = clientKey;
    },

    async beforeCreatePayment() {
      const { transactionId, error: submitError } =
        await fbnKkpayGatewayApi.submit();

      if (submitError) {
        throw new Error(submitError);
      }

      // La clé ici "fbn-kkpay-gateway-id" doit correspondre
      // à ce que votre PHP attend dans $gatewayData
      return {
        "fbn-kkpay-gateway-id": transactionId,
      };
    },

    Fields() {
      return window.wp.element.createElement(FbnKkpayGatewayFields);
    },
  };

  /**
   * Enregistrement final
   */
  window.givewp.gateways.register(FbnKkpayGateway);
})();
