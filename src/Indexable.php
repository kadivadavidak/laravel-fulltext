<?php
namespace Swis\LaravelFulltext;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Swis\LaravelFulltext\ModelObserver;


/**
 * Class Indexable
 *
 * @package Swis\LaravelFulltextServiceProvider
 */
trait Indexable {

    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootIndexable(){
        static::observe(new ModelObserver);
    }

    public function getIndexContent(){
        return $this->getIndexDataFromColumns($this->indexContentColumns);
    }

    public function getIndexTitle(){
        return $this->getIndexDataFromColumns($this->indexTitleColumns);
    }

    public function indexedRecord(){
        return $this->morphOne(config('laravel-fulltext.indexed_record_model'), 'indexable');
    }

    public function indexRecord(){
        if(null === $this->indexedRecord){
            $indexedRecordClass = config('laravel-fulltext.indexed_record_model');
            $this->indexedRecord = new $indexedRecordClass;
            $this->indexedRecord->indexable()->associate($this);
        }
        $this->indexedRecord->updateIndex();
    }

    public function unIndexRecord(){
        if(null !== $this->indexedRecord){
            $this->indexedRecord->delete();
        }
    }

    protected function getIndexDataFromColumns($columns){
        $indexData = [];
        foreach($columns as $column){
            if($this->indexDataIsRelation($column)){
                $indexData[] = $this->getIndexValueFromRelation($column);
            } else {
                if(is_string($this->{$column})){
                    $indexData[] = trim($this->{$column});
                } else {
                    $indexData[] = $this->{$column};
                }
            }
        }
        return implode(' ', array_filter($indexData)) . ' ';
    }

    /**
     * @param $column
     * @return bool
     */
    protected function indexDataIsRelation($column)
    {
        return (int)strpos($column, '.') > 0;
    }

    /**
     * @param $column
     * @return string
     */
    protected function getIndexValueFromRelation($column)
    {
        list($relation, $column) = explode('.', $column);
        if(is_null($this->{$relation})){
            return '';
        }

        if($this->{$relation}() instanceof HasOne){
            return $this->{$relation}->{$column};
        } else {
            return $this->{$relation}->pluck($column)->implode(', ');
        }
    }
}
