<?php
namespace Plokko\LaravelFirebase\Traits;

use Illuminate\Database\Eloquent\{
    Model,Builder
};
use Plokko\LaravelFirebase\Relations\SyncWithFirebaseBelongsToMany;

/**
 * Trait SyncRelatedWithFirebase
 * @package Plokko\LaravelFirebase\Traits
 * @mixin Model
 */
trait SyncRelatedWithFirebase
{

    /**
     * Specifies the relations that needs to be automatically synched with firebase
     * Possible item values:
     *  - 'realtion' - relationship name
     *  - 'relation' => function($query){/*filters*\/} - Filtered query
     *  - function(){}:Model|SyncsWithFirebaseCollection - return a custom query to sync
     * @return array relations to be synched with firebase, default []
     */
    protected function getRelationsToSyncWithFirebase(){
        return [];
    }

    /**
     * Manually syncs all related models with Firebase
     * Note: only relations returned by getRelationsToSyncWithFirebase() will be synchronized
     * @param string $only only sync specified relation (if in getRelationsToSyncWithFirebase() array)
     */
    final public function syncRelatedWithFirebase($only=null){
        $related = $this->getRelationsToSyncWithFirebase();
        if($only){
            if(in_array($only,$related)){
                $related = [$only];
            }elseif(array_key_exists($only,$related)){
                $related = [$only => $related[$only]];
            }else{
                return;
            }
        }
        foreach($related AS $k=>$v){
            if(is_numeric($k)){
                if(is_string($v)){
                    //Simple relationship array
                    $this->$v->syncWithFirebase();
                }elseif(is_callable($v)){
                    //Custom query
                    $v()->get()->syncWithFirebase();
                }
            }else{
                if(is_callable($v)){
                    //Query filter
                    $v($this->$k())->get()->syncWithFirebase();
                }
            }
        }
    }

    /**
     * Overrides the BelongsToMany default relationship.
     *
     * @param  Builder  $query
     * @param  Model  $parent
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string  $relationName
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey,
                                        $parentKey, $relatedKey, $relationName = null)
    {
        if(in_array($relationName,$this->getRelationsToSyncWithFirebase())){
            return new SyncWithFirebaseBelongsToMany( $query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
        }
        return parent::newBelongsToMany( $query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }
}