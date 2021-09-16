<?php

namespace App\Http\Controllers\API;

use App\Exceptions\CardServiceException;
use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Services\CardService;
use App\Traits\ErrorResponseTrait;
use Illuminate\Http\Request;

class CardController extends Controller
{
    use ErrorResponseTrait;

    public function __contruct() {
        $this->middleware("auth:api");
    }//end constructor method

    public function index() {
        $user = auth()->user();

        $cards = $user->cards;

        return response()->json(["cards" => $cards]);
    }//end method index

    public function edit(Card $card, Request $request, CardService $cardService) {
        try{
            $card = $cardService->card($card)
            ->updateOrCreateCard($request->all())
            ->save()
            ->getCard();
        }catch(CardServiceException $e) {
            return $this->errorResponse($e);
        }

        return response()->json(["id" => $card->id, "message" => "updated"], 200);
    }//end method edit

    public function show(Card $card) {
        return response()->json(["card" => $card]);
    }//end method show

    public function destroy(Card $card) {
        $card->delete();
        return response()->json(["message" => "Card deleted"]);
    }//end method destroy
    
    public function store(Request $request, CardService $cardService){
        $request->validate([
            "name" => "required",
            "card_number" => "required",
            "expiration_month" => "required",
            "expiration_year" => "required",
        ]);

        try{
            $user = auth()->user();
            $card = $cardService->user($user)
            ->updateOrCreateCard($request->all())
            ->save()
            ->getCard();
        }catch(CardServiceException $e) {
            return $this->errorResponse($e);
        }

        return response()->json(["id" => $card->id, "message" => "created"], 201);
    }//end method store
}//end class CardController
