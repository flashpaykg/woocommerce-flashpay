declare global {
  interface Window {
    wp: any
    wc: any
    EP: any
  }
}

import { registerPaymentMethodByName } from "./helpers/registerPaymentMethodByName"

for (const gateway of window.EP.gateways) {
  registerPaymentMethodByName(gateway)
}
