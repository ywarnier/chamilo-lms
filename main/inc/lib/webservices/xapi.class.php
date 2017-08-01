<?php
/* For licensing terms, see /license.txt */
/**
 * xAPI API
 */
/**
 * Class xAPI
 */
class XAPI
{
    public function __construct()
    {
        //
    }
    /**
     * Test the web service response
     *
     * @url GET /
     */
    public function test()
    {
        return "Hello World";
    }
    /**
     * Statements
     *
     * @url POST /statements
     * @url GET /statements
     * @url HEAD /statements
     */
    public function statements($params = array())
    {
        error_log(print_r($params, 1));
        return "{ 'msg': 'Hello World statements'}";
    }
    /**
     * Actors
     *
     * @url POST /actors
     * @url GET /actors
     * @url HEAD /actors
     */
    public function actors()
    {
        return "{}";
    }
    /**
     * Verbs
     *
     * @url POST /verbs
     * @url GET /verbs
     * @url HEAD /verbs
     */
    public function verbs()
    {
        return "{}";
    }
    /**
     * Activities
     * @param int $id Activity ID
     * @return string
     *
     * @url GET /activities
     * @url HEAD /activities
     */
    public function activities($id)
    {
        error_log(print_r(func_get_args(), 1));
        return "{}";
    }
}
