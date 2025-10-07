const key = "$2b$12$82C876HIXARFRAF8iQB6JO2C5Zc9NeEZqCwcLY2eJe2klTw.EGvWy";


async function axiosPost(data) {
  try {
    
    const response = await axios.post('http://localhost:3000/api/externalData', data, {
      headers: {
        'Access-Control-Allow-Origin': '*'
      }
    });

    // Handle successful response
    console.log("Request successful:", response.data);
    console.log(response.data);
    window.location.href = response.data.data.url;
  } catch (error) {
    if (error.response) {
      // The request was made and the server responded with a status code
      if (error.response.status === 400) {
        console.log("Bad Request:", error.response.data);
      } else if (error.response.status === 401) {
        console.log("Unauthorized:", error.response.data);
      } else if (error.response.status === 404) {
        console.log("Not Found:", error.response.data);
      } else {
        console.log("Error:", error.message);
      }
    } else if (error.request) {
      // The request was made but no response was received
      console.log("No response received:", error.request);
    } else {
      // Something happened in setting up the request
      console.log("Error:", error.message);
    }
  }
}

$(document).ready(function () {

  console.log("----------- SUBMIT EBUWO -----------");

  // Handle form submission
  $("#myForm").submit(function (event) {
    event.preventDefault(); // Prevent form from submitting normally

    let x_test_mode = $("#x_test_mode").val();
    console.log(x_test_mode);
    let x_shopid = $("#x_shopid").val();
    let x_amount = $("#x_amount").val();
    let x_order_id = $("#x_order_id").val();
    let x_response_url = $("#x_response_url").val();
    let x_first_name = $("#x_first_name").val();
    let x_last_name = $("#x_last_name").val();
    let x_company = $("#x_company").val();
    let x_address = $("#x_address").val();
    let x_country = $("#x_country").val();
    let x_state = $("#x_state").val();
    let x_city = $("#x_city").val();
    let x_zip = $("#x_zip").val();
    let x_phone = $("#x_phone").val();
    let x_email = $("#x_email").val();
    let x_ship_to_first_name = $("#x_ship_to_first_name").val();
    let x_ship_to_last_name = $("#x_ship_to_last_name").val();
    let x_ship_to_company = $("#x_ship_to_company").val();
    let x_ship_to_address = $("#x_ship_to_address").val();
    let x_ship_to_country = $("#x_ship_to_country").val();
    let x_ship_to_state = $("#x_ship_to_state").val();
    let x_ship_to_city = $("#x_ship_to_city").val();
    let x_ship_to_zip = $("#x_ship_to_zip").val();
    let x_freight = $("#x_freight").val();
    let x_version = $("#x_version").val();
    let x_platform = $("#x_platform").val();

    var data = {
      x_test_mode: x_test_mode,
      x_shopid: x_shopid,
      x_amount: x_amount,
      x_order_id: x_order_id,
      x_response_url: x_response_url,
      x_first_name: x_first_name,
      x_last_name: x_last_name,
      x_company: x_company,
      x_address: x_address,
      x_country: x_country,
      x_state: x_state,
      x_city: x_city,
      x_zip: x_zip,
      x_phone: x_phone,
      x_email: x_email,
      x_ship_to_first_name: x_ship_to_first_name,
      x_ship_to_last_name: x_ship_to_last_name,
      x_ship_to_company: x_ship_to_company,
      x_ship_to_address: x_ship_to_address,
      x_ship_to_country: x_ship_to_country,
      x_ship_to_state: x_ship_to_state,
      x_ship_to_city: x_ship_to_city,
      x_ship_to_zip: x_ship_to_zip,
      x_freight: x_freight,
      x_platform: x_platform,
      x_version: x_version,
      signed_field_names:
        "x_test_mode,x_shopid,x_amount,x_order_id,x_response_url,x_first_name,x_last_name,x_company,x_address,x_country,x_state,x_city,x_zip,x_phone,x_email,x_ship_to_first_name,x_ship_to_last_name,x_ship_to_company,x_ship_to_address,x_ship_to_country,x_ship_to_state,x_ship_to_city,x_ship_to_zip,x_freight,x_platform,x_version,signed_field_names",
      signature: null,
    };

    var list = 'x_test_mode=' +
      data.x_test_mode +
      ',x_shopid=' +
      data.x_shopid +
      ',x_amount=' +
      data.x_amount +
      ',x_order_id=' +
      data.x_order_id +
      ',x_response_url=' +
      data.x_response_url +
      ',x_first_name=' +
      data.x_first_name +
      ',x_last_name=' +
      data.x_last_name +
      ',x_company=' +
      data.x_company +
      ',x_address=' +
      data.x_address +
      ',x_country=' +
      data.x_country +
      ',x_state=' +
      data.x_state +
      ',x_city=' +
      data.x_city +
      ',x_zip=' +
      data.x_zip +
      ',x_phone=' +
      data.x_phone +
      ',x_email=' +
      data.x_email +
      ',x_ship_to_first_name=' +
      data.x_ship_to_first_name +
      ',x_ship_to_last_name=' +
      data.x_ship_to_last_name +
      ',x_ship_to_company=' +
      data.x_ship_to_company +
      ',x_ship_to_address=' +
      data.x_ship_to_address +
      ',x_ship_to_country=' +
      data.x_ship_to_country +
      ',x_ship_to_state=' +
      data.x_ship_to_state +
      ',x_ship_to_city=' +
      data.x_ship_to_city +
      ',x_ship_to_zip=' +
      data.x_ship_to_zip +
      ',x_freight=' +
      data.x_freight +
      ',x_platform=' +
      data.x_platform +
      `,x_version` +
      data.x_version +
      ',signed_field_names=' +
      "x_test_mode,x_shopid,x_amount,x_order_id,x_response_url,x_first_name,x_last_name,x_company,x_address,x_country,x_state,x_city,x_zip,x_phone,x_email,x_ship_to_first_name,x_ship_to_last_name,x_ship_to_company,x_ship_to_address,x_ship_to_country,x_ship_to_state,x_ship_to_city,x_ship_to_zip,x_freight,x_platform,x_version,signed_field_names" +
      "";

  

    // console.log(data);

    //Create signature
    var hash = CryptoJS.HmacSHA256(list, key);
    var hashInBase64 = CryptoJS.enc.Base64.stringify(hash);

    // console.log(hashInBase64);

    //add signature to data
    data.signature = hashInBase64;

    console.log(data);

    localStorage.setItem("PayzyOderData", JSON.stringify(data));
    // console.log(JSON.parse(sessionStorage.getItem("PayzyOderData")));

    axiosPost(data);
  });
});
