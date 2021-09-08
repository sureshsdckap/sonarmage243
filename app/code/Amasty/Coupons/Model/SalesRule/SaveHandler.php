<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Model\SalesRule;

use Amasty\Coupons\Api\Data\RuleInterface;
use Amasty\Coupons\Model\RuleFactory;
use Amasty\Coupons\Model\ResourceModel\Rule;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Magento\SalesRule\Api\Data\RuleInterface as SalesRuleInterface;
use Amasty\Coupons\Model\Config;
use Amasty\Coupons\Api\RuleRepositoryInterface;

/**
 * Save Extension Attributes of Rule entity
 */
class SaveHandler implements ExtensionInterface
{
    /**
     * @var RuleFactory
     */
    private $amRuleFactory;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    public function __construct(
        RuleFactory $amRuleFactory,
        MetadataPool $metadataPool,
        Config $config,
        RuleRepositoryInterface $ruleRepository
    ) {
        $this->amRuleFactory = $amRuleFactory;
        $this->metadataPool = $metadataPool;
        $this->config = $config;
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\SalesRule\Model\Rule|\Magento\SalesRule\Model\Data\Rule $entity
     * @param array $arguments
     *
     * @return \Magento\SalesRule\Model\Rule|\Magento\SalesRule\Model\Data\Rule
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Exception
     */
    public function execute($entity, $arguments = [])
    {
        $linkField = $this->metadataPool->getMetadata(SalesRuleInterface::class)->getLinkField();
        $attributes = $entity->getExtensionAttributes() ?: [];

        if (isset($attributes[RuleInterface::EXTENSION_CODE])) {
            $ruleLinkId = $entity->getDataByKey($linkField);
            $inputData = $attributes[RuleInterface::EXTENSION_CODE];

            if ($inputData[RuleInterface::USE_CONFIG_VALUE]) {
                $inputData[RuleInterface::ALLOW_COUPONS_SAME_RULE] = (int)$this->config->isAllowCouponsSameRule();
            } elseif ($inputData[RuleInterface::ALLOW_COUPONS_SAME_RULE] === 'true') {
                $inputData->setAllowCouponsSameRule(true);
            }

            if ($inputData instanceof RuleInterface) {
                $amRule = $inputData;
            } else {
                /** @var RuleInterface $amRule */
                $amRule = $this->amRuleFactory->create(['data' => $inputData]);
            }

            if ($amRule->getRuleId() != $ruleLinkId) {
                $amRule->setId(null);
                $amRule->setRuleId($ruleLinkId);
            }
            $this->ruleRepository->save($amRule, $ruleLinkId);
        }

        return $entity;
    }
}
