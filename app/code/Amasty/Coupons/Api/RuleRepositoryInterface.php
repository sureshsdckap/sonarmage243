<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Api;

/**
 * @api
 */
interface RuleRepositoryInterface
{
    /**
     * Save
     *
     * @param \Amasty\Coupons\Api\Data\RuleInterface $rule
     *
     * @return \Amasty\Coupons\Api\Data\RuleInterface
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function save(\Amasty\Coupons\Api\Data\RuleInterface $rule);

    /**
     * Get by id
     *
     * @param int $entityId
     *
     * @return \Amasty\Coupons\Api\Data\RuleInterface|null
     */
    public function getById(int $entityId): ?\Amasty\Coupons\Api\Data\RuleInterface;

    /**
     * Delete
     *
     * @param \Amasty\Coupons\Api\Data\RuleInterface $rule
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Amasty\Coupons\Api\Data\RuleInterface $rule);

    /**
     * Delete by id
     *
     * @param int $entityId
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($entityId);
}
