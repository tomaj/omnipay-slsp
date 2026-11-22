---
layout: home

hero:
  name: Omnipay SporoPay
  text: SporoPay for Omnipay
  tagline: Signed payment redirects to Slovenská sporiteľňa internet banking, verified returns bound to the order and its amount, and a test mode against a payment simulator.
  image:
    src: /logo.svg
    alt: Omnipay SporoPay
  actions:
    - theme: brand
      text: Get started
      link: /guide/getting-started
    - theme: alt
      text: Operations
      link: /guide/operations
    - theme: alt
      text: GitHub
      link: https://github.com/tomaj/omnipay-slsp

features:
  - title: Omnipay gateway
    details: The SporoPay gateway plugs Slovenská sporiteľňa's payment button into the Omnipay API you already use for other payment providers.
  - title: 3DES signatures
    details: sign1 and SIGN2 are computed with OpenSSL (DES-EDE3-CBC) and compared in constant time, with test vectors checked against the openssl CLI.
  - title: Returns bound to the order
    details: completePurchase() rejects a correctly signed return of another order, another amount or another currency.
  - title: Test mode
    details: Send payments to the hosted payment simulator or your own instance before you go live.
---
