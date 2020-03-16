<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CatController extends Controller
{
	const CAT_API = "https://api.thecatapi.com/v1/";
	const BREEDS_ENDPOINT = "breeds/";
	private $catToken = '';

    public function __construct()
    {
        $this->middleware('auth:api');

        $user = auth()->user();
        $this->catToken = $user->cat_token;
    }

    public function list(Request $request)
    {
    	$curl = curl_init();

    	curl_setopt_array($curl, array(
    	  CURLOPT_URL => self::CAT_API . self::BREEDS_ENDPOINT,
    	  CURLOPT_RETURNTRANSFER => true,
    	  CURLOPT_ENCODING => "",
    	  CURLOPT_MAXREDIRS => 10,
    	  CURLOPT_TIMEOUT => 30,
    	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    	  CURLOPT_CUSTOMREQUEST => "GET",
    	  CURLOPT_HTTPHEADER => array(
    	    "x-api-key: {$this->catToken}"
    	  ),
    	));

    	$response = curl_exec($curl);
    	$err = curl_error($curl);

    	curl_close($curl);

    	if ($err) {
    	  	return response()->json(['error' => $err, 401]);
    	} else {
    		$data = json_decode($response);
    	  	return response()->json(['data' => $data, 200]);
    	}
    }

    public function setCatToken(Request $request)
    {
    	$catToken = $request->cat_token;

    	if($catToken) {
    		try {
				$user = auth()->user();
				$user->cat_token = $catToken;
				$user->save();
    		} catch (\Exception $e) {
    			return response()->json(['message' => $e], 401);
    		}

    		return response()->json(['message' => "Success!"]);
    	} else {
    		return response()->json(['message' => "Token not found!", 401]);
    	}
    }
}
