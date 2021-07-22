<?php

/* Chargify API Helper Form template - Product Form */

?>
<h1>Product Form</h1>
<p>* required</p>
<p id="product_form_error"></p>
<form name="chargify_product_form" onsubmit="return validateForm();" method="post">
    <label for="product">Product</label>
    [product_select]
    <br />
    <label for="first_name">First Name</label>
    <input type="text" id="first_name" name="first_name"><br />
    <label for="last_name">Last Name</label>
    <input type="text" id="last_name" name="last_name"><br />
    <label for="credit_card">Credit Card Number*</label>
    <input type="text" id="credit_card" name="credit_card"><br />
    <label for="exp_date">Expiration Date*</label>
    <select id="exp_month" name="exp_date[month]">
        [exp_month_options]
    </select>
    <select id="exp_year" name="exp_date[year]">
        [exp_year_options]
    </select>
    <br />
    <label for="cvv">CVV*</label>
    <input type="text" id="cvv" name="cvv"><br />
    <label for="zip_code">Zip Code*</label>
    <input type="text" id="zip_code" name="zip_code"><br />
    <input type="submit" value="Submit">
</form>