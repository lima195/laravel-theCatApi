<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use App\Http\Requests\ListCatsRequest;
use App\Http\Requests\SearchCatsRequest;
use Illuminate\Support\Facades\Cache;

class CatController extends Controller
{
	const CAT_API = "https://api.thecatapi.com/v1/";
	const BREEDS_ENDPOINT = "breeds/";

	const DEFAULT_PAGE = 0;
	const DEFAULT_LIMIT = 20;

	private $_catToken = '';

	public function __construct()
	{
		$this->middleware('auth:api');

		$user = auth()->user();
		$this->_catToken = $user->cat_token;
	}

	public function list(ListCatsRequest $request)
	{
		$attach_breed 	= ($request->attach_breed ? $request->attach_breed : 0);
		$page 			= ($request->page ? $request->page : self::DEFAULT_PAGE);
		$limit 			= ($request->limit ? $request->limit : self::DEFAULT_LIMIT);

		$cacheKey = 'list_' . $attach_breed . '_' . $page . '_' . $limit;

		/* Check if cache exists */
		if (Cache::has($cacheKey)) {
			/* Cache exists, so retrive data from cache */
			$response = Cache::get($cacheKey);
		} else {
			/* Cache do not exists, continue to get list from cat api */
			$response = Curl::to(self::CAT_API . self::BREEDS_ENDPOINT)
							->withData([
								'attach_breed' => $attach_breed,
								'page' => $page,
								'limit' => $limit,
							])
							->withHeader("x-api-key: {$this->_catToken}")
							->asJson()
							->get();

			if(isset($response->status) && isset($response->message)) {
				return response()->json(['error' => $response->message], $response->status);
			} else {
				/* Storage response in cache, so next time that we get this request, retrive from cache */
				Cache::put($cacheKey, $response, 360);
			}
		}

		return response()->json(['data' => $response], 200);
	}

	public function search(SearchCatsRequest $request)
	{
		$breed_name = ($request->breed_name ? $request->breed_name : 0);

		$cacheKey = 'search_' . urlencode($breed_name);

		/* Check if cache exists */
		if (Cache::has($cacheKey)) {
			/* Cache exists, so retrive data from cache */
			$response = Cache::get($cacheKey);
		} else {
			/* Cache do not exists, continue to get breed from cat api */
			$response = Curl::to(self::CAT_API . self::BREEDS_ENDPOINT . 'search')
							->withData([
								'q' => $breed_name,
							])
							->withHeader("x-api-key: {$this->_catToken}")
							->asJson()
							->get();
			if(isset($response->status) && isset($response->message)) {
				return response()->json(['error' => $response->message], $response->status);
			} else {
				/* Storage response in cache, so next time that we get this request, retrive from cache */
				Cache::put($cacheKey, $response, 360);
			}
		}

		return response()->json(['data' => $response], 200);
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
