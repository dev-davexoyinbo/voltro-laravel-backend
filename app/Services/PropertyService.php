<?php

namespace App\Services;

use App\Exceptions\PropertyServiceException;
use App\Models\Property;
use App\Models\PropertyFeature;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyService
{
    private Property $property;
    private User $user;
    private RoleAndPriviledgeService $roleAndPriviledgeService;

    public function __construct()
    {
        $this->roleAndPriviledgeService = App::make(RoleAndPriviledgeService::class);
    } //end constructor

    // ==============================================
    // STATIC FUNCTIONS
    // ==============================================

    public static function getPropertyWithSlug(string $slug): Property
    {
        $property = Property::where("slug", $slug)->first();

        if ($property == null) {
            throw new PropertyServiceException("Property not found", 404);
        }

        return $property;
    } //end method getPropertyWithSlug

    // ==============================================
    // MEMBER FUNCTIONS
    // ==============================================

    public function user(User $user): PropertyService
    {
        $this->user = $user;

        return $this;
    } //end method user

    public function clearUser(): PropertyService
    {
        unset($this->user);

        return $this;
    } //end method clearUser

    public function property(Property $property): PropertyService
    {
        $this->property = $property;

        return $this;
    } //end method property

    public function getProperty(): Property
    {
        return $this->property;
    } //end method getProperty

    public function clearProperty(): PropertyService
    {
        unset($this->property);

        return $this;
    } //end method clearProperty

    /**
     * @param array|object $data
     * @param array|object $excludeColumns
     * @param Illuminate\Http\Request $request
     * For an update, the user must be passed into the user() 
     * 
     * Usage example
     * 
     * $updatedOrNewProperty = $propertyService
     *          ->clearUser()
     *          ->user($user)
     *          ->clearProperty()
     *          ->property($property)
     *          ->updateOrCreateProperty(["about" => "lorem ipsum"])
     *          ->save()
     *          ->getProperty();
     */
    public function updateOrCreateProperty($data, $excludeColumns = []): PropertyService
    {
        // NOTE: check the save() method for the updating of the slug


        // exclude some columns because they are not nmecessarily strings
        // or the data might be handled differently
        $excludeDataColumns = ["gallery", "type", "status", "other_features"];

        $excludeDataColumns = array_merge($excludeDataColumns, $excludeColumns);

        // if property is not defined create a new one
        $property = $this->property ?? new Property();
        $columns = Schema::getColumnListing($property->getTable());

        // loop through all data column and update the property column with
        // the value from the data if it exists in the $data object
        foreach ($columns as $column) {
            //if column is to be excluded
            if (in_array($column, $excludeDataColumns)) continue;

            $property[$column] = $data[$column] ?? $property[$column];

            //unset null $property properties to allow the database assign a default
            //value for default column values
            if ($property[$column] == null)
                unset($property[$column]);
        } //end columns loop


        //=============================
        // handle gallery file upload
        if ($data["gallery"]) {
            $property->gallery = $this->handleGalleryUpload($property->gallery ?? []);
        }

        //=============================
        // handle other_features
        if ($data["other_features"]) {
            $property->other_features = $this
                ->handleOtherFeatures(
                    json_decode($data["other_features"], true)
                );
        }

        //=============================
        // handle type
        if ($data["type"]) {
            $property->type = $this
                ->handleType(
                    $data["type"]
                );
        }

        //=============================
        // handle status
        if ($data["status"]) {
            $property->status = $this
                ->handleStatus(
                    $data["status"]
                );
        }

        $this->property($property);
        return $this;
    } //end method updateOrCreateProperty


    public function save(): PropertyService
    {
        $property = $this->property;
        $user = $this->user;

        //update
        if ($property->id) {
            //ensure user has the permission to update
            $canUpdate = $this->roleAndPriviledgeService
                ->clearUser()
                ->user($user)
                ->hasPermission("property_update");
            if (!$canUpdate) {
                throw new PropertyServiceException("This user can't update a property");
            }
            $property->save();
        }
        //new
        else {
            //create a slug for new properties
            $property->slug = substr(Str::slug($property->title), 0, 15) . now()->timestamp;

            //ensure the user has the permission to create property
            $canCreate = $this->roleAndPriviledgeService
                ->clearUser()
                ->user($user)
                ->hasPermission("property_create");
            if (!$canCreate) {
                throw new PropertyServiceException("This user can't create a property");
            }
            $user->properties()
                ->save($property);
        }

        return $this;
    } //end method save

    // ================================
    // PRIVATE METHODS
    // ================================

    private function handleGalleryUpload(array $previousGallery)
    {
        $files = request()->file("gallery");
        $paths = [];

        //save the files
        foreach ($files as $file) {
            $path = Storage::disk("public")->put("property_gallery", $file);
            $paths[] = Storage::url($path); //add "/storage/" to the start of the path
        }

        //delete previous files in gallery
        if ($previousGallery && count($previousGallery) > 0) {
            foreach ($previousGallery as $previousUrl) {
                $deletePath = preg_replace("/^\/storage/", "", $previousUrl);
                Storage::disk("public")->delete($deletePath);
            }
        }

        return $paths;
    } //end method handleGalleryUpload

    private function handleOtherFeatures(array $newFeatures)
    {
        $returnValue = [];

        $propertyFeatureConstants = PropertyFeature::getConstants();
        foreach ($newFeatures as $feature) {
            if (!array_key_exists($feature, $propertyFeatureConstants)) {
                $keys = implode(",", array_keys($propertyFeatureConstants));
                throw new PropertyServiceException("The property feature identifier '$feature' does not exist. Value must be $keys", 400);
            }

            $returnValue[] = $propertyFeatureConstants[$feature];
        }

        return $returnValue;
    } //end method handleOtherFeatures

    public function handleType(string $newType)
    {
        $propertyTypeConstants = PropertyType::getConstants();
        if (!array_key_exists($newType, $propertyTypeConstants)) {
            $keys = implode(",", array_keys($propertyTypeConstants));
            throw new PropertyServiceException("The property type identifier '$newType' does not exist. Value must be $keys", 400);
        }

        return $propertyTypeConstants[$newType];
    } //end method handleType

    public function handleStatus(string $newStatus)
    {
        $propertyStatusConstants = PropertyStatus::getConstants();
        if (!array_key_exists($newStatus, $propertyStatusConstants)) {
            $keys = implode(",", array_keys($propertyStatusConstants));
            throw new PropertyServiceException("The property status identifier '$newStatus' does not exist. Value must be $keys", 400);
        }

        return $propertyStatusConstants[$newStatus];
    } //end method handleStatus
}//end class PropertyService