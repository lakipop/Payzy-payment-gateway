const key = "$2b$12$82C876HIXARFRAF8iQB6JO2C5Zc9NeEZqCwcLY2eJe2klTw.EGvWy";



// http://127.0.0.1:8080/responce.html?x_order_id=x_order_id&response_code=00&signature=OgpL2A3Pejwf+/GWGCvqREy1wQJ+jH3dOa2p+WobE4o= // example return


http: $(document).ready(function () {
  console.log("ready!");

  // Get the query parameters from the current URL
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);

  // Get the value of the 'x_order_id' parameter
  const x_order_id = urlParams.get("x_order_id");
  console.log(x_order_id);

  // Get the value of the 'response_code' parameter
  const response_code = urlParams.get("response_code");
  console.log(response_code);

  // Get the value of the 'response_code' parameter
  const signature = urlParams.get("signature");
  console.log(signature);

  // Oder Data for the session storage
  let order = JSON.parse(localStorage.getItem("PayzyOderData"));
  console.log(order);

  //   Create data list using Oder data and Response code
  let list =
    "response_code=" +
    "00" +
    ",x_test_mode=" +
    order.x_test_mode +
    ",x_shopid=" +
    order.x_shopid +
    ",x_amount=" +
    order.x_amount +
    ",x_order_id=" +
    order.x_order_id +
    ",x_response_url=" +
    order.x_response_url +
    ",x_first_name=" +
    order.x_first_name +
    ",x_last_name=" +
    order.x_last_name +
    ",x_company=" +
    order.x_company +
    ",x_address=" +
    order.x_address +
    ",x_country=" +
    order.x_country +
    ",x_state=" +
    order.x_state +
    ",x_city=" +
    order.x_city +
    ",x_zip=" +
    order.x_zip +
    ",x_phone=" +
    order.x_phone +
    ",x_email=" +
    order.x_email +
    ",x_ship_to_first_name=" +
    order.x_ship_to_first_name +
    ",x_ship_to_last_name=" +
    order.x_ship_to_last_name +
    ",x_ship_to_company=" +
    order.x_ship_to_company +
    ",x_ship_to_address=" +
    order.x_ship_to_address +
    ",x_ship_to_country=" +
    order.x_ship_to_country +
    ",x_ship_to_state=" +
    order.x_ship_to_state +
    ",x_ship_to_city=" +
    order.x_ship_to_city +
    ",x_ship_to_zip=" +
    order.x_ship_to_zip +
    ",x_freight=" +
    order.x_freight +
    ",x_platform=" +
    order.x_platform +
    ",x_version=" +
    order.x_version +
    ",signed_field_names=" +
    "response_code,x_test_mode,x_shopid,x_amount,x_order_id,x_response_url,x_first_name,x_last_name,x_company,x_address,x_country,x_state,x_city,x_zip,x_phone,x_email,x_ship_to_first_name,x_ship_to_last_name,x_ship_to_company,x_ship_to_address,x_ship_to_country,x_ship_to_state,x_ship_to_city,x_ship_to_zip,x_freight,x_platform,x_version,signed_field_names" +
    "";

  //Create signature
  var hash = CryptoJS.HmacSHA256(list.toString(), key);
  var hashInBase64 = CryptoJS.enc.Base64.stringify(hash);

  //   Print signature
  console.log(hashInBase64);

  var newSignature = signature.replace(/\s/g, "+");

  if (hashInBase64 == newSignature) {
    // Signature Matched

    console.log("Signature Matched");

    // complete the order process and shipment and others

    // after the all work done then clean the local storage

    // sessionStorage.removeItem("PayzyOderData");  // remove comment to clear session storage
  } else {
    // Signature Not Matched
    // Do your work here
    console.log("Signature Not Matched");
  }
});
