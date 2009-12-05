<?php

class AkActiveRecordCounter extends AkActiveRecordExtenssion
{
    /**
    * Increments the specified counter by one. So $DiscussionBoard->incrementCounter("post_count",
    * $discussion_board_id); would increment the "post_count" counter on the board responding to
    * $discussion_board_id. This is used for caching aggregate values, so that they doesn't need to
    * be computed every time. Especially important for looping over a collection where each element
    * require a number of aggregate values. Like the $DiscussionBoard that needs to list both the number of posts and comments.
    */
    public function incrementCounter($counter_name, $id, $difference = 1)
    {
        return $this->_ActiveRecord->updateAll("$counter_name = $counter_name + $difference", $this->_ActiveRecord->getPrimaryKey().' = '.$this->_ActiveRecord->castAttributeForDatabase($this->_ActiveRecord->getPrimaryKey(), $id)) === 1;
    }

    /**
    * Works like AkActiveRecord::incrementCounter, but decrements instead.
    */
    public function decrementCounter($counter_name, $id, $difference = 1)
    {
        return $this->_ActiveRecord->updateAll("$counter_name = $counter_name - $difference", $this->_ActiveRecord->getPrimaryKey().' = '.$this->_ActiveRecord->castAttributeForDatabase($this->_ActiveRecord->getPrimaryKey(), $id)) === 1;
    }

    /**
    * Initializes the attribute to zero if null and subtracts one. Only makes sense for number-based attributes. Returns attribute value.
    */
    public function decrementAttribute($attribute)
    {
        if(!isset($this->_ActiveRecord->$attribute)){
            $this->_ActiveRecord->$attribute = 0;
        }
        return $this->_ActiveRecord->$attribute -= 1;
    }

    /**
    * Decrements the attribute and saves the record.
    */
    public function decrementAndSaveAttribute($attribute)
    {
        return $this->_ActiveRecord->updateAttribute($attribute,$this->decrementAttribute($attribute));
    }


    /**
    * Initializes the attribute to zero if null and adds one. Only makes sense for number-based attributes. Returns attribute value.
    */
    public function incrementAttribute($attribute)
    {
        if(!isset($this->_ActiveRecord->$attribute)){
            $this->_ActiveRecord->$attribute = 0;
        }
        return $this->_ActiveRecord->$attribute += 1;
    }

    /**
    * Increments the attribute and saves the record.
    */
    public function incrementAndSaveAttribute($attribute)
    {
        return $this->_ActiveRecord->updateAttribute($attribute, $this->incrementAttribute($attribute));
    }

}
