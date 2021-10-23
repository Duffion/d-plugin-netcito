import $ from "jquery";

let FE_SC_HELPER = {

    styles: {
        message: { paddingTop: '2px', paddingBottom: '1px' }
    },

    api: {
        subdomain: false,
        key: false,
        thankyou: false,
        response: false,
        query: {
            // product_handle: false,
            product_id: false,
            customer_attributes: {
                first_name: '',
                last_name: '',
                email: '',
            },
            credit_card_attributes: {
                first_name: false,
                last_name: false,
                card_type: false,
                full_number: false,
                expiration_month: false,
                expiration_year: false,
                // billing_address: false,
                // billing_address_2: false,
                // billing_city: false,
                // billing_state: false,
                billing_zip: false,
                billing_country: 'usa',
                current_fault: 'stripe',
            }
        }
    },

    instance: {},

    targets: {},

    chargify: false,

    init: function () {
        console.log('initalize the helper');
        this.define();
        let api = this.api;
        let config = {
            publicKey: api.key,
            selector: '.d-chargify-form',
            type: 'card',
            serverHost: 'https://' + api.subdomain + '.chargify.com',
            fields: {
                // firstName: {
                //     selector: '#first_name',
                //     label: 'First Name',
                //     placeholder: 'John',
                //     required: true,
                //     message: 'First name is not valid. Please update it.',
                //     maxlength: '30',
                //     style: {

                //         message: { paddingTop: '2px', paddingBottom: '1px' }
                //     }
                // },
                // lastName: {
                //     selector: '#last_name',
                //     label: 'Last Name',
                //     placeholder: 'Doe',
                //     required: true,
                //     message: 'This field is not valid. Please update it.',
                //     maxlength: '30',
                //     style: {
                //         message: { paddingTop: '2px', paddingBottom: '1px' }
                //     }
                // },
                number: {
                    selector: '#number',
                    label: 'Number',
                    placeholder: 'xxxx xxxx xxxx xxxx',
                    message: 'This field is not valid. Please update it.',
                    style: {
                        message: { paddingTop: '2px', paddingBottom: '1px' }
                    }
                },
                month: {
                    selector: '#month',
                    label: 'Mon',
                    placeholder: 'mm',
                    message: 'This field is not valid. Please update it.',
                    style: {
                        message: { paddingTop: '2px', paddingBottom: '1px' }
                    }
                },
                year: {
                    selector: '#year',
                    label: 'Year',
                    placeholder: 'yyyy',
                    message: 'This field is not valid. Please update it.',
                    style: {
                        message: { paddingTop: '2px', paddingBottom: '1px' }
                    }
                },
                cvv: {
                    selector: '#cvv',
                    label: 'CVV code',
                    placeholder: '123',
                    required: false,
                    message: 'This field is not valid. Please update it.',
                    style: {
                        message: { paddingTop: '2px', paddingBottom: '1px' }
                    }
                },
                // zip: {
                //     selector: '#zip_code',
                //     label: 'Zip Code',
                //     placeholder: '10001',
                //     required: false,
                //     message: 'This field is not valid. Please update it.',
                //     maxlength: '5',
                //     style: {
                //         message: { paddingTop: '2px', paddingBottom: '1px' }
                //     }
                // }
            }
        };
        this.chargify = new Chargify();
        console.log('config', config);
        this.chargify.load(config);

        this.register();

        console.log('API Info', this.api, this.chargify);
    },

    register: function () {
        if (this.targets.forms.length > 0) {
            this.targets.forms.on('submit', this.handle.submission);
        }

        setTimeout(() => {
            var iframe = $('#first_name iframe');
            // console.log('iframe', iframe, $(document)[0]['get %card%#first_name']());
            // console.log('test', $('.cfy-input'), this.targets.forms.find('iframe'), this.targets.forms.find('iframe')[0], $(this.targets.forms.find('iframe')[0]).contents());
        }, 3000);

        if (typeof this.targets.coupon !== 'undefined' && this.targets.coupon.length > 0) {
            this.targets.coupon.on('click', this.handle.toggleCoupon);
        }
    },

    define: function () {
        // setup our api keys and our rest agent //
        var forms = $('[data-d-chargify]');
        this.targets.forms = {};

        if (forms.length > 0) {
            this.api.key = forms.data('d-chargify');
            this.api.subdomain = forms.data('d-subdomain');
            this.api.thankyou = forms.data('d-thank-you');

            this.targets.forms = forms;
            this.targets.coupon = forms.find('[data-chargify-coupon="toggle"]');
        }
    },

    fill_query: function (values) {
        var query = FE_SC_HELPER.api.query;
        if (values) {
            console.log('fill query', values);
            query.customer_attributes.first_name = values['first_name'];
            query.customer_attributes.last_name = values['last_name'];
            query.customer_attributes.email = values['email'];

            query.credit_card_attributes.first_name = values['first_name'];
            query.credit_card_attributes.last_name = values['last_name'];
            query.credit_card_attributes.card_type = (typeof values['card_type'] !== 'undefined' ? values['card_type'] : FE_SC_HELPER.instance.card_type);
            query.credit_card_attributes.full_number = values['credit_card'];
            query.credit_card_attributes.expiration_month = values['exp_date[month]'];
            query.credit_card_attributes.expiration_year = values['exp_date[year]'];
            query.credit_card_attributes.billing_zip = values['zip_code'];

            query.product_id = values['product'];
        }

        FE_SC_HELPER.instance.query = query;

        return query;
    },

    subscribe: function () {
        // var q = { subscription: query },
        //     api = FE_SC_HELPER.api;


        // // lets post it
        // FE_SC_HELPER.chargify_rc.post('subscriptions.json', q).then(function (res) {
        //     console.log('results', res);
        // }).catch(function (err) {
        //     console.log('error', err);
        // });
    },

    form: {
        validate: function (values, form) {
            let required = ['first_name', 'last_name', 'email', 'zip_code'];
            // Make sure our required values are here //
            console.log('Submitting', form, FE_SC_HELPER);
            // Lets now run our primary card validation //
            FE_SC_HELPER.chargify.token(
                form[0],
                function success(token) {
                    console.log('Success token', token, form, values);
                    // lets fill out the data properly //
                    form.find('input[name="d_chargify_token"]').val(token);

                    console.log('submitting', form, form.find('input[name="d_chargify_token"]').val());
                    // lets post the form //
                    form[0].submit();
                },
                function error(err) {
                    console.log('token error', err);
                    // there was an error, handle it //
                }
            );
        }
    },

    handle: {
        toggleCoupon: function (e) {
            var couponField = FE_SC_HELPER.targets.coupon.parent().find('#coupon');
            console.log('field', couponField);
            if (couponField.hasClass('hidden')) {
                // show //
                couponField.removeClass('hidden').fadeIn(300);
                $(this).text('Remove Coupon Code');
            } else {
                // hide //
                couponField.addClass('hidden').fadeOut(300).find('input').val('');
                $(this).text('Add Coupon Code');
            }
        },
        action: function (e) {
            e.preventDefault();
            var action = $(this).data('chargify-coupon');


        },
        submission: function (e) {
            e.preventDefault();
            let values = {};
            // lets define our form instance //
            $.each($(this).serializeArray(), function (i, field) {
                values[field.name] = field.value;
            });
            console.log('Submitted', values);

            FE_SC_HELPER.form.validate(values, $(this));
        },

        failed: function () {

        },

        success: function () {

        }
    }

};

$(function () {
    FE_SC_HELPER.init();
});

