 <div class="shadow-sm bg-white  border bg-white p-3 p-lg-4 text-left">

     <div class="modal-body">
         <div class="row">
             <div class="col-md-2">
                 <label>{{ translate('Name') }}
                     <span class="text-danger">*</span>
                 </label>
             </div>
             <div class="col-md-10">
                 <input class="form-control mb-3 border " placeholder="{{ translate('Name') }}" rows="2"
                     name="name" id="name" required>
             </div>
         </div>
         <div class="row">
             <div class="col-md-2">
                 <label>{{ translate('Phone') }}
                     <small class="text-danger">*</small>
                 </label>
             </div>
             <div class="col-md-10 mb-3">
                 <input type="text" class="form-control border " placeholder="{{ translate('Ex:012xxxxxxxx') }}"
                     name="phone" id="phone" value="{{ @$userAddress->phone }}" pattern="\d{11}" required>
                 <small class="text-muted mb-3">Please enter an 11-digit phone
                     number.</small>
             </div>
             {{-- pattern="\d{15,}"  --}}
         </div>
         <div class="row">
             <div class="col-md-2">
                 <label>{{ translate('Address') }}</label>
             </div>
             <div class="col-md-10">
                 <div class="form-group">
                     <textarea name="address" id="" class="form-control border " placeholder="Enter Address here..." rows="3">{{ @$address->address ?? @$userAddress->address }}</textarea>
                 </div>
             </div>
         </div>
         <div class="row">
             <div class="col-md-2">
                 <label>{{ translate('Area') }} <small class="text-danger">*</small> </label>
             </div>
             <div class="col-md-10">
                 <div class="form-group">
                    <select id="shipping_cost" name="shipping_cost" class="form-control" required>
                        <option value=""> {{ translate('Select Area') }} </option>

                        <option value="{{ get_setting('inside_dhaka_value') }}"
                            @selected(session('delivery_charge') == get_setting('inside_dhaka_value'))>
                            {{ translate('Inside Dhaka') }} ({{ get_setting('inside_dhaka_value') }}) টাকা
                        </option>

                        <option value="{{ get_setting('outside_dhaka_value') }}"
                            @selected(session('delivery_charge') == get_setting('outside_dhaka_value'))>
                            {{ translate('Outside Dhaka') }} ({{ get_setting('outside_dhaka_value') }}) টাকা
                        </option>
                    </select>



                 </div>
             </div>
         </div>


         <div>
             <div class="card shadow-sm border-0 rounded">
                 <div class="card-header p-3">
                     <h3 class="fs-16 fw-600 mb-0">
                         {{ translate('Select a payment option') }}
                     </h3>
                 </div>

                 <div class="card-body text-center">
                     <div class="row gutters-10">
                         @if (get_setting('paypal_payment') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="paypal" class="online_payment" type="radio" name="payment_option"
                                         checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/paypal.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-13">{{ translate('Paypal') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('stripe_payment') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="stripe" class="online_payment" type="radio" name="payment_option"
                                         checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/stripe.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-13">{{ translate('Stripe') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('sslcommerz_payment') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="sslcommerz" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/sslcommerz.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-13">{{ translate('sslcommerz') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('instamojo_payment') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="instamojo" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/instamojo.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-15">{{ translate('Instamojo') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('razorpay') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="razorpay" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/rozarpay.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-15">{{ translate('Razorpay') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('paystack') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="paystack" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/paystack.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-15">{{ translate('Paystack') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('voguepay') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="voguepay" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/vogue.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-15">{{ translate('VoguePay') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('payhere') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="payhere" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/payhere.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-15">{{ translate('payhere') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('ngenius') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="ngenius" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/ngenius.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-15">{{ translate('ngenius') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('iyzico') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="iyzico" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/iyzico.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-15">{{ translate('Iyzico') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('nagad') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="nagad" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/nagad.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-15">{{ translate('Nagad') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('bkash') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="bkash" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/bkash.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-15">{{ translate('Bkash') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('aamarpay') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="aamarpay" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/aamarpay.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-15">{{ translate('Aamarpay') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('authorizenet') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="authorizenet" class="online_payment" type="radio"
                                         name="payment_option" checked style="border-radius: 5px !important">
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/authorizenet.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span
                                                 class="d-block fw-600 fs-15">{{ translate('Authorize Net') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('payku') == 1)
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="payku" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/payku.png') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-15">{{ translate('Payku') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (addon_is_activated('african_pg'))
                             @if (get_setting('mpesa') == 1)
                                 <div class="col-6 col-md-6 col-lg-4">
                                     <label class="aiz-megabox d-block mb-3">
                                         <input value="mpesa" class="online_payment" type="radio"
                                             name="payment_option" checked>
                                         <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                             style="border-radius: 5px !important">
                                             <img src="{{ static_asset('assets/img/cards/mpesa.png') }}"
                                                 class="img-fluid mb-2">
                                             <span class="d-block text-center">
                                                 <span class="d-block fw-600 fs-15">{{ translate('mpesa') }}</span>
                                             </span>
                                         </span>
                                     </label>
                                 </div>
                             @endif
                             @if (get_setting('flutterwave') == 1)
                                 <div class="col-6 col-md-6 col-lg-4">
                                     <label class="aiz-megabox d-block mb-3">
                                         <input value="flutterwave" class="online_payment" type="radio"
                                             name="payment_option" checked>
                                         <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                             style="border-radius: 5px !important">
                                             <img src="{{ static_asset('assets/img/cards/flutterwave.png') }}"
                                                 class="img-fluid mb-2">
                                             <span class="d-block text-center">
                                                 <span
                                                     class="d-block fw-600 fs-15">{{ translate('flutterwave') }}</span>
                                             </span>
                                         </span>
                                     </label>
                                 </div>
                             @endif
                             @if (get_setting('payfast') == 1)
                                 <div class="col-6 col-md-6 col-lg-4">
                                     <label class="aiz-megabox d-block mb-3">
                                         <input value="payfast" class="online_payment" type="radio"
                                             name="payment_option" checked>
                                         <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                             style="border-radius: 5px !important">
                                             <img src="{{ static_asset('assets/img/cards/payfast.png') }}"
                                                 class="img-fluid mb-2">
                                             <span class="d-block text-center">
                                                 <span class="d-block fw-600 fs-15">{{ translate('payfast') }}</span>
                                             </span>
                                         </span>
                                     </label>
                                 </div>
                             @endif
                         @endif
                         @if (addon_is_activated('paytm'))
                             <div class="col-6 col-md-6 col-lg-4">
                                 <label class="aiz-megabox d-block mb-3">
                                     <input value="paytm" class="online_payment" type="radio"
                                         name="payment_option" checked>
                                     <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                         style="border-radius: 5px !important">
                                         <img src="{{ static_asset('assets/img/cards/paytm.jpg') }}"
                                             class="img-fluid mb-2">
                                         <span class="d-block text-center">
                                             <span class="d-block fw-600 fs-15">{{ translate('Paytm') }}</span>
                                         </span>
                                     </span>
                                 </label>
                             </div>
                         @endif
                         @if (get_setting('cash_payment') == 1)
                             @php
                                 $digital = 0;
                                 $cod_on = 1;
                                 foreach ($carts as $cartItem) {
                                     $product = \App\Models\Product::find($cartItem['product_id']);
                                     if ($product['digital'] == 1) {
                                         $digital = 1;
                                     }
                                     if ($product['cash_on_delivery'] == 0) {
                                         $cod_on = 0;
                                     }
                                 }
                             @endphp
                             @if ($digital != 1 && $cod_on == 1)
                                 <div class="col-6 col-md-6 col-lg-4">
                                     <label class="aiz-megabox d-block mb-3">
                                         <input value="cash_on_delivery" class="online_payment" type="radio"
                                             name="payment_option" checked>
                                         <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                             style="border-radius: 5px !important">
                                             <img src="{{ static_asset('assets/img/cards/cod.png') }}"
                                                 class="img-fluid mb-2">
                                             <span class="d-block text-center">
                                                 <span
                                                     class="d-block fw-600 fs-13">{{ translate('Cash on Delivery') }}</span>
                                             </span>
                                         </span>
                                     </label>
                                 </div>
                             @endif
                         @endif
                         @if (Auth::check())
                             @if (addon_is_activated('offline_payment'))
                                 @foreach (\App\Models\ManualPaymentMethod::all() as $method)
                                     <div class="col-6 col-md-6 col-lg-4">
                                         <label class="aiz-megabox d-block mb-3">
                                             <input value="{{ $method->heading }}" type="radio"
                                                 name="payment_option"
                                                 onchange="toggleManualPaymentData({{ $method->id }})"
                                                 data-id="{{ $method->id }}" checked>
                                             <span class="d-block p-3 aiz-megabox-elem aiz_megabox_elem_new"
                                                 style="border-radius: 5px !important">
                                                 <img src="{{ uploaded_asset($method->photo) }}"
                                                     class="img-fluid mb-2">
                                                 <span class="d-block text-center">
                                                     <span class="d-block fw-600 fs-15">{{ $method->heading }}</span>
                                                 </span>
                                             </span>
                                         </label>
                                     </div>
                                 @endforeach

                                 @foreach (\App\Models\ManualPaymentMethod::all() as $method)
                                     <div id="manual_payment_info_{{ $method->id }}" class="d-none">
                                         @php echo $method->description @endphp
                                         @if ($method->bank_info != null)
                                             <ul>
                                                 @foreach (json_decode($method->bank_info) as $key => $info)
                                                     <li>{{ translate('Bank Name') }}
                                                         -
                                                         {{ $info->bank_name }},
                                                         {{ translate('Account Name') }}
                                                         -
                                                         {{ $info->account_name }},
                                                         {{ translate('Account Number') }}
                                                         -
                                                         {{ $info->account_number }},
                                                         {{ translate('Routing Number') }}
                                                         -
                                                         {{ $info->routing_number }}
                                                     </li>
                                                 @endforeach
                                             </ul>
                                         @endif
                                     </div>
                                 @endforeach
                             @endif
                         @endif
                     </div>

                     @if (addon_is_activated('offline_payment'))
                         <div class="bg-white border mb-3 p-3 rounded text-left d-none">
                             <div id="manual_payment_description">

                             </div>
                         </div>
                     @endif
                 </div>


             </div>

         </div>

     </div>
     <button class="btn btn-primary fs-14 fw-700 rounded-0 px-4 w-100 ">অর্ডার কনফার্ম করুন</button>

 </div>


 @section('script')
     <script>
         $(document).ready(function() {

             function shipping_charge() {
                 let shipping_charge = $('#shipping_cost').val() || 0;
                 updateSessionVariable('delivery_charge', shipping_charge); // Update session variable

                 console.log(shipping_charge);


                 let total = Number('{{ $total }}'); // Convert total to number
                 let total_amount = total + Number(shipping_charge || 0); // Handle NaN case

                 $('.total_amount').html('৳ ' + total_amount);
                 $('.shipping_amount').html('৳ ' + shipping_charge);
             }

             shipping_charge(); // Call function on page load

             $('#shipping_cost').on('change', function() {
                 shipping_charge(); // Call function on change
             });

         });
         
         function removeFromCartView(e, key) {
            e.preventDefault();
            removeFromCart(key);
        }


         function updateQuantity(key, element) {
            let shipping_charge = $(this);
            chnage_value(element)

             $.post('{{ route('cart.updateQuantity') }}', {
                 _token: AIZ.data.csrf,
                 id: key,
                 quantity: element.value
             }, function(data) {
                 updateNavCart(data.nav_cart_view, data.cart_count);
                 $('#cart-details').html(data.cart_view);
                 AIZ.extra.plusMinus();
             });
         }

         function chnage_value(element) {
                let quantityInput = $(element).closest('.aiz-plus-minus').find('input');
                let currentQuantity = parseInt(quantityInput.val(), 10);
                console.log("currentQuantity",currentQuantity)
                let pricePerUnit = $(element).data("price");
                let cartId = $(element).data("cart-id");

                // Adjust quantity based on button clicked
                if ($(element).data("type") === "plus") {
                    currentQuantity++;
                } else if ($(element).data("type") === "minus" && currentQuantity > 1) {
                    currentQuantity--;
                }

                // Update the input value
                quantityInput.val(currentQuantity);

                // Calculate new total price for this item
                let newTotal = (currentQuantity * pricePerUnit).toFixed(2);

                // Update the displayed price for the item
                $(element).closest("li").find(".price_change").text("৳ " + newTotal);

                // Recalculate overall cart total
                updateCartTotal();
            };

            function updateCartTotal() {
        let newTotal = 0;
        $(".price_change").each(function() {
            newTotal += parseFloat($(this).text().replace('৳', '').trim());
        });

        let shippingCost = parseFloat($('.shipping_amount').text().replace('৳', '').trim());
        let sub_totalAmount = newTotal ;
        let totalAmount = newTotal + shippingCost;

        // Update the total amount in the cart
        $('.sub_total').html('৳ ' + sub_totalAmount);
        $('.total_amount').html('৳ ' + totalAmount.toFixed(2));
    }



         function updateSessionVariable(variableName, value) {
             $.ajax({
                 type: 'POST',
                 url: '/update-session',
                 data: {
                     _token: AIZ.data.csrf,
                     variableName: variableName,
                     value: value
                 },
                 success: function(response) {},
                 error: function(error) {
                     console.error('Error updating session variable:', error);
                 }
             });
         }
     </script>
 @endsection
