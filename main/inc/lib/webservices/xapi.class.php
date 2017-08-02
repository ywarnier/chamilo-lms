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
        error_log(__CLASS__.' constructor called');
    }
    /**
     * Test the web service response
     * @return string
     *
     * @url GET /
     */
    public function test()
    {
        error_log(__METHOD__.' called on class '.__CLASS__);
        return "Hello World";
    }
    /**
     * Statements
     * @return string
     *
     * @url POST /statements
     */
    public function statements($request)
    {
        error_log(__FUNCTION__.' -- '.print_r($request->getContent(), 1));
        return "{ 'msg': 'Hello World statements'}";
    }
    /**
     * Actors
     * @return string
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
     * @return string
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
        error_log(__FUNCTION__.' -- '.print_r(func_get_args(), 1));
        return "{}";
    }
}
