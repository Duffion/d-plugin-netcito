<?php

/* Chargify API Helper Form template - Product Form */
$uid = uuid();

?>
<link rel="stylesheet" id="chargify-css" href="https://js.chargify.com/latest/hosted-field.css">
<script src="https://js.chargify.com/latest/chargify.js"></script>
<h1>Product Form</h1>
<p>* required</p>
<p id="product_form_error"></p>
<form name="chargify_product_form" action="[admin-post]" class="d-chargify-form" method="post" data-d-chargify="[api-key]" data-d-thank-you="[thank-you]" data-d-subdomain="[subdomain]">

    <div class="field-group[hide-product-select]">
        <div class="cfy-field cfy-field--product">
            <label class="cfy-label cfy-label--product" for="product">
                Product
            </label>
            <div class="cfy-input-container">
                [product_select]
            </div>
        </div>
    </div>

    <div class="field-group">
        <div id="first_name" class="host-field">
            <div class="cfy-field cfy-field--first_name"><label class="cfy-label cfy-label--first_name" for="cfy-first_name">First Name</label>
                <div class="cfy-input-container"><input class="cfy-input cfy-input--first_name" id="cfy-first_name" name="first_name" maxlength="30" autocomplete="first_name" placeholder="John"></div>
            </div>
        </div>

        <div id="last_name" class="host-field">
            <div class="cfy-field cfy-field--last_name"><label class="cfy-label cfy-label--last_name" for="cfy-last_name">Last Name</label>
                <div class="cfy-input-container"><input class="cfy-input cfy-input--last_name" id="cfy-last_name" name="last_name" maxlength="40" autocomplete="last_name" placeholder="Doe"></div>
            </div>
        </div>
    </div>
    <div class="field-group" id="email">
        <div class="cfy-field cfy-field--email"><label class="cfy-label cfy-label--email" for="cfy-email">Email</label>
            <div class="cfy-input-container"><input class="cfy-input cfy-input--email" id="cfy-email" name="email" maxlength="40" type="email" autocomplete="email" placeholder="john@doe.com"></div>
        </div>
    </div>
    <div class="field-group" id="number"></div>
    <div class="field-group" id="month"></div>
    <div class="field-group" id="year"></div>
    <div class="field-group" id="cvv"></div>

    <div class="field-group" id="zip_code">
        <div class="cfy-field cfy-field--zip"><label class="cfy-label cfy-label--zip" for="cfy-zip">Zip Code</label>
            <div class="cfy-input-container"><input class="cfy-input cfy-input--zip" id="cfy-zip" name="zip" maxlength="5" autocomplete="postal-code" placeholder="10001"></div>
        </div>
        <div class="field-group coupon-code">
            <a href="javascript: void(0);" data-chargify-coupon="toggle" class="btn btn-primary cfy-action">Add a Coupon Code</a>
            <div class="field-group--coupon-input hidden" id="coupon">
                <div class="cfy-field cfy-field--coupon"><label class="cfy-label cfy-label--coupon" for="cfy-zip">Coupon Code</label>
                    <div class="cfy-input-container"><input class="cfy-input cfy-input--coupon" id="cfy-coupon" name="coupon" autocomplete="coupon_code" placeholder="Enter your coupon code..."></div>
                </div>
            </div>
        </div>
    </div>

    <div class="field-group hidden">
        <input type="hidden" name="d_chargify_nonce" value="[nonce]" />
        <input type="hidden" name="d_chargify_token" value="" />
        <input type="hidden" name="action" value="submit_subscription" />
    </div>

    <div class="field-group">
        <input type="submit" value="Submit">
    </div>
</form>