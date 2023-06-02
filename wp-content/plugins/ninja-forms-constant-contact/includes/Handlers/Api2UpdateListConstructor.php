<?php

namespace NinjaForms\ConstantContact\Handlers;

/**
 * Construct subscriber body with new list added
 *
 */
class Api2UpdateListConstructor
{

    /**
     * Member data
     *
     * @var array
     */
    protected $memberData;

    /**
     * 
     * @var array
     */
    protected $updatedSubscriber;

    /**
     * JSON encoded subscribe request body
     *
     * @var string
     */
    protected $jsonEncodedBody = '';

    /**
     * Construct subscriber body with new list added
     *
     * @param array $memberData
     * @param array $matchedSubscriber
     * @return Api2UpdateListConstructor
     */
    public function handle(array $memberData, array $matchedSubscriber): Api2UpdateListConstructor
    {
        $this->memberData = $memberData;
        $this->updatedSubscriber = $matchedSubscriber;

        if (isset($this->memberData['lists'])) {
            $this->maybeAddNewList($this->memberData['lists']);
        }

        $this->jsonEncodedBody = \json_encode($this->updatedSubscriber);

        return $this;
    }

    protected function maybeAddNewList(string $listId): void
    {
        if (
            isset($this->updatedSubscriber['lists'])
            && \is_array($this->updatedSubscriber['lists'])
        ) {
            foreach ($this->updatedSubscriber['lists'] as $listArray) {

                if (
                    isset($listArray['id'])
                    && $listId == $listArray['id']
                ) {
                    return;
                }
            }

            $this->updatedSubscriber['lists'][] = [
                'id' => $listId,
                'status' => 'ACTIVE'
            ];
        }
    }

    /**
     * Get JSON encoded subscribe request body
     *
     * @return  string
     */
    public function getJsonEncodedBody(): string
    {
        return $this->jsonEncodedBody;
    }
}
