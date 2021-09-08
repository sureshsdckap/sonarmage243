<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Model\Repository;

use Amasty\Coupons\Api\Data\RuleInterface;
use Amasty\Coupons\Api\RuleRepositoryInterface;
use Amasty\Coupons\Model\RuleFactory;
use Amasty\Coupons\Model\ResourceModel\Rule as RuleResource;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 * Repository of Coupon Rules
 */
class RuleRepository implements RuleRepositoryInterface
{
    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var RuleResource
     */
    private $ruleResource;

    /**
     * Model data storage
     *
     * @var array
     */
    private $rules = [];

    public function __construct(
        RuleFactory $ruleFactory,
        RuleResource $ruleResource
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->ruleResource = $ruleResource;
    }

    /**
     * @inheritdoc
     */
    public function save(RuleInterface $rule)
    {
        try {
            if ($rule->getEntityId()) {
                $rule = $this->getById($rule->getRuleId())->addData($rule->getData());
            }
            $this->ruleResource->save($rule);
            unset($this->rules[$rule->getRuleId()]);
        } catch (\Exception $e) {
            if ($rule->getEntityId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save rule with ID %1. Error: %2',
                        [$rule->getEntityId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new rule. Error: %1', $e->getMessage()));
        }

        return $rule;
    }

    /**
     * Get by SalesRule link id
     *
     * @param int $entityId
     *
     * @return \Amasty\Coupons\Api\Data\RuleInterface|null
     */
    public function getById(int $entityId): ?RuleInterface
    {
        if (!array_key_exists($entityId, $this->rules)) {
            /** @var \Amasty\Coupons\Model\Rule $rule */
            $rule = $this->ruleFactory->create();
            $this->ruleResource->load($rule, $entityId, RuleInterface::KEY_SALESRULE_ID);
            if (!$rule->getRuleId()) {
                $rule = null;
            }
            $this->rules[$entityId] = $rule;
        }

        return $this->rules[$entityId];
    }

    /**
     * @inheritdoc
     */
    public function delete(RuleInterface $rule)
    {
        try {
            $this->ruleResource->delete($rule);
            unset($this->rules[$rule->getRuleId()]);
        } catch (\Exception $e) {
            if ($rule->getRuleId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove rule with ID %1. Error: %2',
                        [$rule->getRuleId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove rule. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($entityId)
    {
        $ruleModel = $this->getById($entityId);
        $this->delete($ruleModel);

        return true;
    }
}
