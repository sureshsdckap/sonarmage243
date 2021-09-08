<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Model;

use Amasty\Coupons\Api\Data\RuleInterface;
use Amasty\Coupons\Api\Data\RuleInterfaceFactory;
use Amasty\Coupons\Model\Repository\RuleRepository;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\SalesRule\Api\Data\RuleExtensionFactory;

class RuleResolver
{
    /**
     * @var RuleExtensionFactory
     */
    private $extensionFactory;

    /**
     * @var MetadataPool
     */
    private $metadata;

    /**
     * @var RuleRepository
     */
    private $ruleRepository;

    public function __construct(
        RuleExtensionFactory $extensionFactory,
        MetadataPool $metadata,
        RuleRepository $ruleRepository
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->metadata = $metadata;
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * @param \Magento\SalesRule\Model\Rule $salesRule
     *
     * @return Rule|null
     */
    public function getCouponRule(\Magento\SalesRule\Model\Rule $salesRule): ?RuleInterface
    {
        $extensionAttributes = $salesRule->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->extensionFactory->create();
        }
        if (!$extensionAttributes->getAmcoupons()
            && $amRule = $this->ruleRepository->getById($this->getLinkId($salesRule))
        ) {
            $extensionAttributes->setAmcoupons($amRule);
        }
        $salesRule->setExtensionAttributes($extensionAttributes);

        return $extensionAttributes->getAmcoupons();
    }

    /**
     * @param \Magento\Rule\Model\AbstractModel $rule
     * @return int|null
     */
    public function getLinkId(\Magento\Rule\Model\AbstractModel $rule): int
    {
        return (int)$rule->getDataByKey($this->getLinkField());
    }

    /**
     * @return string
     */
    public function getLinkField(): string
    {
        return $this->metadata->getMetadata(\Magento\SalesRule\Api\Data\RuleInterface::class)->getLinkField();
    }
}
