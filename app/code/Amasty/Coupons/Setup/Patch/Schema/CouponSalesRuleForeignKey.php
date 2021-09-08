<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Setup\Patch\Schema;

use Amasty\Coupons\Api\Data\RuleInterface;
use Amasty\Coupons\Model\ResourceModel\Rule;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\SalesRule\Api\Data\RuleInterface as SalesRuleInterface;

/**
 * Patch dynamically add foreign key for sales_rule table.
 * Link column depends on metadata.
 */
class CouponSalesRuleForeignKey implements SchemaPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var MetadataPool
     */
    private $metadata;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        MetadataPool $metadata
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->metadata = $metadata;
    }

    /**
     * Add Foreign Key.
     *
     * @return void
     */
    public function apply()
    {
        $metadata = $this->metadata->getMetadata(SalesRuleInterface::class);
        $this->moduleDataSetup->getConnection()->addForeignKey(
            $this->moduleDataSetup->getConnection()->getForeignKeyName(
                Rule::TABLE_NAME,
                RuleInterface::KEY_SALESRULE_ID,
                $metadata->getEntityTable(),
                $metadata->getLinkField()
            ),
            $this->moduleDataSetup->getTable(Rule::TABLE_NAME),
            RuleInterface::KEY_SALESRULE_ID,
            $this->moduleDataSetup->getTable($metadata->getEntityTable()),
            $metadata->getLinkField()
        );
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * This version associate patch with Module setup version.
     *
     * @return string
     */
    public static function getVersion()
    {
        return '1.1.0';
    }
}
