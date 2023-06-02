<?php 
namespace NinjaForms\ConstantContact\Handlers;

/**
 * Constructs request body for API v2 `subscribe` request
 */
class Api2SubscribeBodyConstructor{


    /**
     * Member data
     *
     * @var array
     */
    protected $memberData;

    /**
     * JSON encoded subscribe request body
     *
     * @var string
     */
    protected $jsonEncodedBody='';

    /**
     * Construct request body for API v2 `subscribe` request
     *
     * @param array $memberData
     * 
     * @return Api2SubscribeBodyConstructor
     */
    public function handle(array $memberData ): Api2SubscribeBodyConstructor
    {
        $this->memberData = $memberData;

        $this->jsonEncodedBody = json_encode(array(
            'email_addresses' => array(
                array(
                    'email_address' => $this->memberData['email'],
                    'opt_in_source' => 'ACTION_BY_VISITOR'
                ),
            ),
            'first_name' => !empty($this->memberData['fields']['first_name']) ? $this->memberData['fields']['first_name'] : '',
            'last_name'  => !empty($this->memberData['fields']['last_name']) ? $this->memberData['fields']['last_name'] : '',
            
            'lists'  => array(
                array(
                    'id' => $this->memberData['lists'],
                )
            )
        ));

        return $this;
    }

    /**
     * Get JSON encoded subscribe request body
     *
     * @return  string
     */ 
    public function getJsonEncodedBody():string
    {
        return $this->jsonEncodedBody;
    }
}