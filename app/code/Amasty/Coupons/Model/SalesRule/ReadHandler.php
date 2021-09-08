<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Model\SalesRule;

use Amasty\Coupons\Api\Data\RuleInterface;
use Amasty\Coupons\Model\RuleFactory;
use Amasty\Coupons\Model\ResourceModel\Rule as RuleResource;
use Amasty\Coupons\Model\Rule;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Magento\SalesRule\Api\Data\RuleInterface as SalesRuleInterface;
use Amasty\Coupons\Model\Config;

/**
 * Add Extension Attributes to Sales Rules entity on load
 */
class ReadHandler implements ExtensionInterface
{
    /**
     * @var RuleResource
     */
    private $ruleResource;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var RuleFactory
     */
    private $amRuleFactory;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        RuleFactory $amRuleFactory,
        RuleResource $ruleResource,
        MetadataPool $metadataPool,
        Config $config
    ) {
        $this->ruleResource = $ruleResource;
        $this->metadataPool = $metadataPool;
        $this->amRuleFactory = $amRuleFactory;
        $this->config = $config;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\SalesRule\Model\Rule|\Magento\SalesRule\Model\Data\Rule $entity
     * @param array $arguments
     *
     * @return \Magento\SalesRule\Model\Rule|\Magento\SalesRule\Model\Data\Rule
     *
     * @throws \Exception
     */
    public function execute($entity, $arguments = [])
    {
        $linkField = $this->metadataPool->getMetadata(SalesRuleInterface::class)->getLinkField();
        $ruleLinkId = $entity->getDataByKey($linkField);

        if ($ruleLinkId) {
            /** @var array $attributes */
            $attributes = $entity->getExtensionAttributes() ?: [];
            /** @var Rule $amRule */
            $amRule = $this->amRuleFactory->create();
            $this->ruleResource->load($amRule, $ruleLinkId, RuleInterface::KEY_SALESRULE_ID);

            if ($amRule->getUseConfigValue()) {
                $amRule->setAllowCouponsSameRule($this->config->isAllowCouponsSameRule());
            } elseif ((int)$amRule->getAllowCouponsSameRule()) {
                $amRule->setAllowCouponsSameRule(true);
            }

            $attributes[RuleInterface::EXTENSION_CODE] = $amRule;
            $entity->setData(RuleInterface::EXTENSION_CODE, $amRule);
            $entity->setExtensionAttributes($attributes);
        }

        return $entity;
    }
}
