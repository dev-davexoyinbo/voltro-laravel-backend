<?php

namespace App\Services;

use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CardService
{
    private User $user;
    private Card $card;

    public function user(User $user): CardService
    {
        $this->user = $user;

        return $this;
    } //end method user

    public function card(Card $card): CardService {
        $this->card = $card;
        return $this;
    }//end method card

    public function getCard(){
        return $this->card;
    }//end methdo getCard

    public function clearUser(): CardService
    {
        unset($this->user);

        return $this;
    } //end method clearUser

    public function clearCard(): CardService
    {
        unset($this->card);

        return $this;
    } //end method clearCard

    /**
     * @param array|object $data
     * @param array|object $excludeColumns
     * @param Illuminate\Http\Request $request
     * For an update, the user must be passed into the user() 
     * 
     * Usage example
     * 
     * $updatedOrNewUser = $CardService
     *          ->clearUser()
     *          ->user($user)
     *          ->updateOrCreateCard(["about" => "lorem ipsum"])
     *          ->save()
     *          ->getUser();
     */
    public function updateOrCreateCard($data, $excludeColumns = []): CardService
    {
        // exclude some columns because they are not nmecessarily strings
        // or the data might be handled differently
        $excludeDataColumns = ["id"];

        $excludeDataColumns = array_merge($excludeDataColumns, $excludeColumns);

        // if user is not defined create a new one
        $card = $this->card ?? new Card();
        $columns = Schema::getColumnListing($card->getTable());

        // loop through all data column and update the card column with
        // the value from the data if it exists in the $data object
        foreach ($columns as $column) {
            //if column is to be excluded
            if (in_array($column, $excludeDataColumns)) continue;

            $card[$column] = $data[$column] ?? $card[$column];

            //unset null $user properties to allow the database assign a default
            //value for default column values
            if ($card[$column] == null)
                unset($card[$column]);
        } //end columns loop

        //=================================
        // Handle excluded columns
        //================================

        $this->card($card);
        return $this;
    } //end method updateOrCreateCard

    public function save(): CardService
    {
        if($this->card->id) {
            $this->card->save();
        }else {
            $this->user->cards()->save($this->card);
        }
        return $this;
    } //end method save
}//end class CardService