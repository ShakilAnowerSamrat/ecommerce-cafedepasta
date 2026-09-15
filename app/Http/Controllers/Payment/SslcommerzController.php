<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CombinedOrder;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\WalletController;
use App\Models\CustomerPackage;
use App\Models\SellerPackage;
use App\Http\Controllers\CustomerPackageController;
use App\Http\Controllers\SellerPackageController;
use App\Http\Controllers\SSLCommerz;
use App\Models\User;
use Session;
use Auth;

session_start();

class SslcommerzController extends Controller
{
    public function pay(Request $request)
    {
        # Here you have to receive all the order data to initate the payment.
        # Lets your oder trnsaction informations are saving in a table called "orders"
        # In orders table order uniq identity is "order_id","order_status" field contain status of the transaction, "grand_total" is the order amount to be paid and "currency" is for storing Site Currency which will be checked with paid currency.
        if(Session::has('payment_type')){
            if(Session::get('payment_type') == 'cart_payment'){
                $combined_order = CombinedOrder::findOrFail($request->session()->get('combined_order_id'));
                $post_data = array();
                $post_data['total_amount'] = $combined_order->grand_total; # You cant not pay less than 10
                $post_data['currency'] = "BDT";
                $post_data['tran_id'] = substr(md5($request->session()->get('combined_order_id')), 0, 10); // tran_id must be unique

                $post_data['value_a'] = $post_data['tran_id'];
                $post_data['value_b'] = $request->session()->get('combined_order_id');
                $post_data['value_c'] = $request->session()->get('payment_type');

                #Start to save these value  in session to pick in success page.
                // $_SESSION['payment_values']['tran_id']=$post_data['tran_id'];
                // $_SESSION['payment_values']['order_id']=$request->session()->get('order_id');
                // $_SESSION['payment_values']['payment_type']=$request->session()->get('payment_type');
                #End to save these value  in session to pick in success page.

                # CUSTOMER INFORMATION
                // $post_data['cus_name'] = $request->session()->get('shipping_info')['name'];
                // $post_data['cus_add1'] = $request->session()->get('shipping_info')['address'];
                // $post_data['cus_city'] = $request->session()->get('shipping_info')['city'];
                // $post_data['cus_postcode'] = $request->session()->get('shipping_info')['postal_code'];
                // $post_data['cus_country'] = $request->session()->get('shipping_info')['country'];
                // $post_data['cus_phone'] = $request->session()->get('shipping_info')['phone'];
                // $post_data['cus_email'] = $request->session()->get('shipping_info')['email'];
                
            }
            elseif (Session::get('payment_type') == 'wallet_payment') {
                $post_data = array();
                $post_data['total_amount'] = $request->session()->get('payment_data')['amount']; # You cant not pay less than 10
                $post_data['currency'] = "BDT";
                $post_data['tran_id'] = substr(md5(Auth::user()->id), 0, 10); // tran_id must be unique

                $post_data['value_a'] = $post_data['tran_id'];
                $post_data['value_b'] = $request->session()->get('payment_data')['amount'];
                $post_data['value_c'] = $request->session()->get('payment_type');
                $post_data['value_d'] = Auth::user()->id;

                #Start to save these value  in session to pick in success page.
                // $_SESSION['payment_values']['tran_id']=$post_data['tran_id'];
                // $_SESSION['payment_values']['payment_data']=$request->session()->get('payment_data');
                // $_SESSION['payment_values']['payment_type']=$request->session()->get('payment_type');
                #End to save these value  in session to pick in success page.
                
            }
            elseif (Session::get('payment_type') == 'customer_package_payment') {
                $customer_package = CustomerPackage::findOrFail(Session::get('payment_data')['customer_package_id']);
                $post_data = array();
                $post_data['total_amount'] = $customer_package->amount; # You cant not pay less than 10
                $post_data['currency'] = "BDT";
                $post_data['tran_id'] = substr(md5(Auth::user()->id), 0, 10); // tran_id must be unique

                $post_data['value_a'] = $post_data['tran_id'];
                $post_data['value_b'] = $request->session()->get('payment_data')['customer_package_id'];
                $post_data['value_c'] = $request->session()->get('payment_type');
                $post_data['value_d'] = Auth::user()->id;

                #Start to save these value  in session to pick in success page.
                // $_SESSION['payment_values']['tran_id']=$post_data['tran_id'];
                // $_SESSION['payment_values']['payment_data']=$request->session()->get('payment_data');
                // $_SESSION['payment_values']['payment_type']=$request->session()->get('payment_type');
                #End to save these value  in session to pick in success page.
            }
            elseif (Session::get('payment_type') == 'seller_package_payment') {
                $seller_package = SellerPackage::findOrFail(Session::get('payment_data')['seller_package_id']);
                $post_data = array();
                $post_data['total_amount'] = $seller_package->amount; # You cant not pay less than 10
                $post_data['currency'] = "BDT";
                $post_data['tran_id'] = substr(md5(Auth::user()->id), 0, 10); // tran_id must be unique

                $post_data['value_a'] = $post_data['tran_id'];
                $post_data['value_b'] = $request->session()->get('payment_data')['seller_package_id'];
                $post_data['value_c'] = $request->session()->get('payment_type');
                $post_data['value_d'] = Auth::user()->id;

                #Start to save these value  in session to pick in success page.
                // $_SESSION['payment_values']['tran_id']=$post_data['tran_id'];
                // $_SESSION['payment_values']['payment_data']=$request->session()->get('payment_data');
                // $_SESSION['payment_values']['payment_type']=$request->session()->get('payment_type');
                #End to save these value  in session to pick in success page.

            }
            
            # CUSTOMER INFORMATION
            $user = Auth::user();
            $post_data['cus_name'] = $user->name;
            $post_data['cus_add1'] = $user->address;
            $post_data['cus_city'] = $user->city;
            $post_data['cus_postcode'] = $user->postal_code;
            $post_data['cus_country'] = $user->country;
            $post_data['cus_phone'] = $user->phone;
            $post_data['cus_email'] = $user->email;
        }

        $server_name=$request->root()."/";
        $post_data['success_url'] = $server_name . "sslcommerz/success";
        $post_data['fail_url'] = $server_name . "sslcommerz/fail";
        $post_data['cancel_url'] = $server_name . "sslcommerz/cancel";
        // dd($post_data);
        # SHIPMENT INFORMATION
        // $post_data['ship_name'] = 'ship_name';
        // $post_data['ship_add1 '] = 'Ship_add1';
        // $post_data['ship_add2'] = "";
        // $post_data['ship_city'] = "";
        // $post_data['ship_state'] = "";
        // $post_data['ship_postcode'] = "";
        // $post_data['ship_country'] = "Bangladesh";

        # OPTIONAL PARAMETERS
        // $post_data['value_a'] = "ref001";
        // $post_data['value_b'] = "ref002";
        // $post_data['value_c'] = "ref003";
        // $post_data['value_d'] = "ref004";

        $sslc = new SSLCommerz();
        
        # initiate(Transaction Data , false: Redirect to SSLCOMMERZ gateway/ true: Show all the Payement gateway here )
        $payment_options = $sslc->initiate($post_data, false);
        if (!is_array($payment_options)) {
            print_r($payment_options);
            $payment_options = array();
        }

    }

    public function success(Request $request)
    {
        $sslc = new SSLCommerz();
        $tran_id = $request->value_a;
        $payment = json_encode($request->all());

        if(!isset($request->value_c)){
            flash(translate('Invalid payment data.'))->error();
            return redirect()->route('home');
        }

        $amount = 0;
        if($request->value_c == 'cart_payment'){
            $combined_order = CombinedOrder::find($request->value_b);
            if (!$combined_order) {
                flash(translate('Order not found.'))->error();
                return redirect()->route('home');
            }
            $amount = $combined_order->grand_total;
        }
        elseif ($request->value_c == 'wallet_payment') {
            $amount = (float)$request->value_b;
        }
        elseif ($request->value_c == 'customer_package_payment') {
            $customer_package = CustomerPackage::find($request->value_b);
            $amount = $customer_package ? $customer_package->amount : 0;
        }
        elseif ($request->value_c == 'seller_package_payment') {
            $seller_package = SellerPackage::find($request->value_b);
            $amount = $seller_package ? $seller_package->amount : 0;
        }

        // Validate transaction with SSLCommerz server before honoring payment
        if (!$request->has('val_id') || !$sslc->orderValidate($tran_id, $amount, "BDT", $request->all())) {
            flash(translate('Payment validation failed.'))->error();
            return redirect()->route('home');
        }

        if($request->value_c == 'cart_payment'){
            return (new CheckoutController)->checkout_done($request->value_b, $payment);
        }
        elseif ($request->value_c == 'wallet_payment') {
            $data['amount'] = $request->value_b;
            $data['payment_method'] = 'sslcommerz';
            if ($request->filled('value_d')) {
                $user = User::find($request->value_d);
                if ($user) {
                    Auth::login($user);
                }
            }

            return (new WalletController)->wallet_payment_done($data, $payment);
        }
        elseif ($request->value_c == 'customer_package_payment') {
            $data['customer_package_id'] = $request->value_b;
            $data['payment_method'] = 'sslcommerz';
            if ($request->filled('value_d')) {
                $user = User::find($request->value_d);
                if ($user) {
                    Auth::login($user);
                }
            }

            return (new CustomerPackageController)->purchase_payment_done($data, $payment);
        }
        elseif ($request->value_c == 'seller_package_payment') {
            $data['seller_package_id'] = $request->value_b;
            $data['payment_method'] = 'sslcommerz';
            if ($request->filled('value_d')) {
                $user = User::find($request->value_d);
                if ($user) {
                    Auth::login($user);
                }
            }

            return (new SellerPackageController)->purchase_payment_done(json_decode($request->value_b), $payment);
        }
    }

    public function fail(Request $request)
    {
        $request->session()->forget('order_id');
        $request->session()->forget('payment_data');
        flash(translate('Payment Failed'))->warning();
        return redirect()->route('home');
    }

     public function cancel(Request $request)
    {
        $request->session()->forget('order_id');
        $request->session()->forget('payment_data');
        flash(translate('Payment cancelled'))->error();
    	return redirect()->route('home');
    }

     public function ipn(Request $request)
    {
        #Received all the payement information from the gateway
      if($request->input('tran_id')) #Check transation id is posted or not.
      {

          $tran_id = $request->input('tran_id');

          #Check order status in order tabel against the transaction id or order id.
          $combined_order = CombinedOrder::findOrFail($request->session()->get('combined_order_id'));

                if($order->payment_status =='Pending')
                {
                    $sslc = new SSLCommerz();
                    $validation = $sslc->orderValidate($tran_id, $order->grand_total, 'BDT', $request->all());
                    if($validation == TRUE)
                    {
                        /*
                        That means IPN worked. Here you need to update order status
                        in order table as Processing or Complete.
                        Here you can also sent sms or email for successfull transaction to customer
                        */
                        echo "Transaction is successfully Complete";
                    }
                    else
                    {
                        /*
                        That means IPN worked, but Transation validation failed.
                        Here you need to update order status as Failed in order table.
                        */

                        echo "validation Fail";
                    }

                }
        }
        else
        {
            echo "Inavalid Data";
        }
    }
}
