<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
class MyBigcomController extends Controller
{
    //
    public function __construct()
    {
      $this->client_id = env('BC_APP_CLIENT_ID');
      $this->client_secret = env('BC_APP_SECRET');
      $this->redirect_uri = "http://localhost/mybigcommapp/public/callback";
    }
   
       
      //Load Callback - it gets called every time you open the app
      public function loadApp(Request $request)
      {
         
          $data = $this->verifySignedRequest($request->get('signed_payload'));
          if (empty($data)) {
              return 'Invalid signed_payload.';
          }
          else{
              session(['store_hash' => $data['store_hash']]);
          }
     
          return view('welcome');
      }

      //it gets called when you install the app. We have shown it here to save the access token with the storehash in the database, which can be used for future purposes.
      public function callBack(Request $request)
        {
            $payload = array(
                'client_id' => env('BC_APP_CLIENT_ID'),
                'client_secret' => env('BC_APP_SECRET'),
                'redirect_uri' => $this->redirect_uri,
                'grant_type' => 'authorization_code',
                'code' => $request->get('code'),
                'scope' => $request->get('scope'),
                'context' => $request->get('context'),
            );
            $client = new Client("https://login.bigcommerce.com");
            $req = $client->post('/oauth2/token', array(), $payload, array(
                'exceptions' => false,
            ));
            $resp = $req->send();
            if ($resp->getStatusCode() == 200) {
                $data = $resp->json();
                list($context, $storehash) = explode('/', $data['context'], 2);
                $key = $this->getUserKey($storehash, $data['user']['email']);
                $access_token = $data['access_token'];
                $storeHash = $data['context'];
                $array = explode('/',$storeHash);
                $storehash = $array[1];
                $email = $data['user']['email'];
                $configValue = Config::select('*')->where('storehash',$storehash)->get()->toArray();
                if(count($configValue) != 0){
                    $id = $configValue[0]['id'];
                    $configObj = Config::find($id);
                    $configObj->access_token = $access_token;
                    $configObj->save();
                }else{
                    $configObj = new Config;
                    $configObj->email = $email;
                    $configObj->storehash = $storehash;
                    $configObj->access_token = $access_token;
                    $configObj->save();
                }
            }
            if ($access_token != '') {
                return 'App Installed Successfully, Reload the page. ';
            }
            else {
                return 'Something went wrong... [' . $resp->getStatusCode() . '] ' . $resp->getBody();
            }
        }
        public function verifySignedRequest($signedRequest)
        {
            list($encodedData, $encodedSignature) = explode('.', $signedRequest, 2);
            $client_secret = env('BC_APP_SECRET');
            // decode the data
            $signature = base64_decode($encodedSignature);
            $jsonStr = base64_decode($encodedData);
            $data = json_decode($jsonStr, true);
            // confirm the signature
            $expectedSignature = hash_hmac('sha256', $jsonStr, $client_secret, $raw = false);
            if (!hash_equals($expectedSignature, $signature)) {
                error_log('Bad signed request from BigCommerce!');
                return null;
            }
            return $data;
        }
}
