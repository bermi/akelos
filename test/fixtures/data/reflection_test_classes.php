<?php
class ReflectionTestClass1 {
    /**
     * testFunction1
     *
     * @param int $param1
     * @param int $param2
     */
    function testFunction1($param1,$param2)
    {
        /**
         * comment
         */
        
    }
    /**
     * testFunction2
     *
     * @param unknown_type $param
     * @return boolean
     * @WingsPluginInstallAs BaseActiveRecord::test
     */
    function &testFunction2(&$param)
    {
        return true;
    }
}

class ReflectionTestClass2 {
    /**
     * testFunction1
     *
     * @param int $param1
     * @param int $param2
     * @WingsPluginInstallAs BaseActiveRecord::test
     */
    function testFunction1($param1,$param2)
    {
        /**
         * comment
         */
        
    }
    /**
     * testFunction2
     *
     * @param unknown_type $param
     * @return boolean
     * 
     */
    function &testFunction2(&$param)
    {
        return true;
    }
}
?>