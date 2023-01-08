<?php
/*
 * @version $Id: transfer.class.php 14684 2011-06-11 06:32:40Z remi $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class Transfer extends CommonDBTM {

   // Specific ones
   /// Already transfer item
   var $already_transfer = array();
   /// Items simulate to move - non recursive item or recursive item not visible in destination entity
   var $needtobe_transfer = array();
   /// Items simulate to move - recursive item visible in destination entity
   var $noneedtobe_transfer = array();
   /// Search in need to be transfer items
   var $item_search = array();
   /// Search in need to be exclude from transfer
   var $item_recurs = array();
   /// Options used to transfer
   var $options = array();
   /// Destination entity id
   var $to = -1;
   /// type of initial item transfered
   var $inittype = 0;
   /// item types which have infocoms
   var $INFOCOMS_TYPES = array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone',
                               'Printer', 'Software', 'SoftwareLicense');
   /// item types which have contracts
   var $CONTRACTS_TYPES = array('Computer', 'Monitor', 'NetworkEquipment','Peripheral', 'Phone',
                                'Printer', 'Software');
   /// item types which have tickets
   var $TICKETS_TYPES = array('Computer', 'Monitor', 'NetworkEquipment','Peripheral', 'Phone',
                              'Printer', 'Software');
   /// item types which have documents
   var $DOCUMENTS_TYPES = array('CartridgeItem', 'Computer', 'ConsumableItem', 'Contact',
                                'Contract', 'Document', 'Monitor', 'NetworkEquipment',
                                'Peripheral', 'Phone', 'Printer', 'Software', 'Supplier');

   var $DEVICES_TYPES = array('DeviceCase', 'DeviceControl', 'DeviceDrive', 'DeviceGraphicCard',
                              'DeviceHardDrive', 'DeviceMemory', 'DeviceMotherboard',
                              'DeviceNetworkCard', 'DevicePci', 'DevicePowerSupply',
                              'DeviceProcessor', 'DeviceSoundCard');


   function canCreate() {
      return haveRight('transfer', 'w');
   }

   function canView() {
      return haveRight('transfer', 'r');
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $ong[1] = $LANG['title'][26];

      return $ong;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][16];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = $LANG['common'][26];
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      return $tab;
   }


   /**
    * Transfer items
    *
    *@param $items items to transfer
    *@param $to entity destination ID
    *@param $options options used to transfer
   **/
   function moveItems($items, $to, $options) {
      global $CFG_GLPI;

      // unset mailing
      $CFG_GLPI["use_mailing"] = 0;

      $this->options = array('keep_ticket'         => 0,
                             'keep_networklink'    => 0,
                             'keep_reservation'    => 0,
                             'keep_history'        => 0,
                             'keep_device'         => 0,
                             'keep_infocom'        => 0,

                             'keep_dc_monitor'     => 0,
                             'clean_dc_monitor'    => 0,

                             'keep_dc_phone'       => 0,
                             'clean_dc_phone'      => 0,

                             'keep_dc_peripheral'  => 0,
                             'clean_dc_peripheral' => 0,

                             'keep_dc_printer'     => 0,
                             'clean_dc_printer'    => 0,

                             'keep_supplier'       => 0,
                             'clean_supplier'      => 0,

                             'keep_contact'        => 0,
                             'clean_contact'       => 0,

                             'keep_contract'       => 0,
                             'clean_contract'      => 0,

                             'keep_disk'           => 0,

                             'keep_software'       => 0,
                             'clean_software'      => 0,

                             'keep_document'       => 0,
                             'clean_document'      => 0,

                             'keep_cartridgeitem'  => 0,
                             'clean_cartridgeitem' => 0,
                             'keep_cartridge'      => 0,

                             'keep_consumable'     => 0);

      if ($to>=0) {
         // Store to
         $this->to = $to;
         // Store options
         if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
               $this->options[$key] = $val;
            }
         }

         // Simulate transfers To know which items need to be transfer
         $this->simulateTransfer($items);

         //printCleanArray($this->needtobe_transfer);

         // Software first (to avoid copy during computer transfer)
         $this->inittype = 'Software';
         if (isset($items['Software']) && count($items['Software'])) {
            foreach ($items['Software'] as $ID) {
               $this->transferItem('Software', $ID, $ID);
            }
         }

         // Computer before all other items
         $this->inittype = 'Computer';
         if (isset($items['Computer']) && count($items['Computer'])) {
            foreach ($items['Computer'] as $ID) {
               $this->transferItem('Computer', $ID, $ID);
            }
         }

         // Inventory Items : MONITOR....
         $INVENTORY_TYPES = array('CartridgeItem', 'ConsumableItem', 'Monitor', 'NetworkEquipment',
                                  'Peripheral', 'Phone', 'Printer', 'SoftwareLicense', );

         foreach ($INVENTORY_TYPES as $itemtype) {
            $this->inittype = $itemtype;
            if (isset($items[$itemtype]) && count($items[$itemtype])) {
               foreach ($items[$itemtype] as $ID) {
                  $this->transferItem($itemtype, $ID, $ID);
               }
            }
         }

         // Clean unused
         $this->cleanSoftwareVersions();

         // Management Items
         $MANAGEMENT_TYPES = array('Contact', 'Contract', 'Document', 'Supplier');
         foreach ($MANAGEMENT_TYPES as $itemtype) {
            $this->inittype = $itemtype;
            if (isset($items[$itemtype]) && count($items[$itemtype])) {
               foreach ($items[$itemtype] as $ID) {
                  $this->transferItem($itemtype, $ID, $ID);
               }
            }
         }

         // Tickets
         $OTHER_TYPES = array('Group', 'Link', 'Ticket');
         foreach ($OTHER_TYPES as $itemtype) {
            $this->inittype = $itemtype;
            if (isset($items[$itemtype]) && count($items[$itemtype])) {
               foreach ($items[$itemtype] as $ID) {
                  $this->transferItem($itemtype, $ID, $ID);
               }
            }
         }
      } // $to >= 0
   }


   /**
    * Add an item in the needtobe_transfer list
    *
    * @param $itemtype of the item
    * @param $ID of the item
   **/
   function addToBeTransfer ($itemtype, $ID) {

      if (!isset($this->needtobe_transfer[$itemtype])) {
         $this->needtobe_transfer[$itemtype] = array();
      }

      // Can't be in both list (in fact, always false)
      if (isset($this->noneedtobe_transfer[$itemtype][$ID])) {
         unset($this->noneedtobe_transfer[$itemtype][$ID]);
      }

      $this->needtobe_transfer[$itemtype][$ID] = $ID;
   }


   /**
    * Add an item in the noneedtobe_transfer list
    *
    * @param $itemtype of the item
    * @param $ID of the item
   **/
   function addNotToBeTransfer ($itemtype, $ID) {

      if (!isset($this->noneedtobe_transfer[$itemtype])) {
         $this->noneedtobe_transfer[$itemtype] = array();
      }

      // Can't be in both list (in fact, always true)
      if (!isset($this->needtobe_transfer[$itemtype][$ID])) {
         $this->noneedtobe_transfer[$itemtype][$ID] = $ID;
      }
   }


   /**
    * simulate the transfer to know which items need to be transfer
    *
    * @param $items Array of the items to transfer
   **/
   function simulateTransfer($items) {
      global $DB, $CFG_GLPI;

      // Init types :
      $types = array('CartridgeItem', 'Computer', 'ConsumableItem', 'Contact', 'Contract',
                     'Document', 'Link', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone',
                     'Printer', 'Software', 'SoftwareLicense', 'SoftwareVersion', 'Supplier',
                     'Ticket');

      foreach ($types as $t) {
         if (!isset($this->needtobe_transfer[$t])) {
            $this->needtobe_transfer[$t] = array();
         }
         if (!isset($this->noneedtobe_transfer[$t])) {
            $this->noneedtobe_transfer[$t] = array();
         }
         $this->item_search[$t] =
                  $this->createSearchConditionUsingArray($this->needtobe_transfer[$t]);
         $this->item_recurs[$t] =
                  $this->createSearchConditionUsingArray($this->noneedtobe_transfer[$t]);
      }

      $to_entity_ancestors = getAncestorsOf("glpi_entities",$this->to);

      // Copy items to needtobe_transfer
      foreach ($items as $key => $tab) {
         if (count($tab)) {
            foreach ($tab as $ID) {
               $this->addToBeTransfer($key,$ID);
            }
         }
      }

      // Computer first
      $this->item_search['Computer'] =
               $this->createSearchConditionUsingArray($this->needtobe_transfer['Computer']);

      // DIRECT CONNECTIONS

      $DC_CONNECT = array();
      if ($this->options['keep_dc_monitor']) {
         $DC_CONNECT[] = 'Monitor';
      }
      if ($this->options['keep_dc_phone']) {
         $DC_CONNECT[] = 'Phone';
      }
      if ($this->options['keep_dc_peripheral']) {
         $DC_CONNECT[] = 'Peripheral';
      }
      if ($this->options['keep_dc_printer']) {
         $DC_CONNECT[] = 'Printer';
      }

      if (count($DC_CONNECT) && count($this->needtobe_transfer['Computer'])>0) {
         foreach ($DC_CONNECT as $itemtype) {
            $itemtable = getTableForItemType($itemtype);
            $item      = new $itemtype();

            // Clean DB / Search unexisting links and force disconnect
            $query = "SELECT `glpi_computers_items`.`id`
                      FROM `glpi_computers_items`
                      LEFT JOIN `$itemtable`
                        ON (`glpi_computers_items`.`items_id` = `$itemtable`.`id` )
                      WHERE `glpi_computers_items`.`itemtype` = '$itemtype'
                            AND `$itemtable`.`id` IS NULL";

            if ($result = $DB->query($query)) {
               if ($DB->numrows($result)>0) {
                  while ($data=$DB->fetch_array($result)) {
                     $conn = new Computer_Item();
                     $conn->delete(array('id'             => $data['id'],
                                         '_no_history'    => true,
                                         '_no_auto_action'=> true));
                  }
               }
            }

            $query = "SELECT DISTINCT `items_id`
                      FROM `glpi_computers_items`
                      WHERE `itemtype` = '$itemtype'
                            AND `computers_id` IN ".$this->item_search['Computer'];

            if ($result = $DB->query($query)) {
               if ($DB->numrows($result)>0) {
                  while ($data=$DB->fetch_array($result)) {

                     if (!class_exists($itemtype)) {
                        continue;
                     }

                     if ($item->getFromDB($data['items_id'])
                         && $item->isRecursive()
                         && in_array($item->getEntityID(), $to_entity_ancestors)) {

                        $this->addNotToBeTransfer($itemtype,$data['items_id']);

                     } else {
                        $this->addToBeTransfer($itemtype,$data['items_id']);
                     }
                  }
               }
            }

            $this->item_search[$itemtype] =
                     $this->createSearchConditionUsingArray($this->needtobe_transfer[$itemtype]);

            if ($item->maybeRecursive()) {
               $this->item_recurs[$itemtype] =
                        $this->createSearchConditionUsingArray($this->noneedtobe_transfer[$itemtype]);
            }
         }
      } // End of direct connections

      // Licence / Software :  keep / delete + clean unused / keep unused
      if ($this->options['keep_software']) {
         // Clean DB
         $query = "SELECT `glpi_computers_softwareversions`.`id`
                   FROM `glpi_computers_softwareversions`
                   LEFT JOIN `glpi_computers`
                      ON (`glpi_computers_softwareversions`.`computers_id` = `glpi_computers`.`id`)
                   WHERE `glpi_computers`.`id` IS NULL";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {
                  $query = "DELETE
                            FROM `glpi_computers_softwareversions`
                            WHERE `id` = '".$data['id']."'";
                  $DB->query($query);
               }
            }
         }

         // Clean DB
         $query = "SELECT `glpi_computers_softwareversions`.`id`
                   FROM `glpi_computers_softwareversions`
                   LEFT JOIN `glpi_softwareversions`
                      ON (`glpi_computers_softwareversions`.`softwareversions_id`
                           = `glpi_softwareversions`.`id`)
                   WHERE `glpi_softwareversions`.`id` IS NULL";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {
                  $query = "DELETE
                            FROM `glpi_computers_softwareversions`
                            WHERE `id` = '".$data['id']."'";
                  $DB->query($query);
               }
            }
         }

         // Clean DB
         $query = "SELECT `glpi_softwareversions`.`id`
                   FROM `glpi_softwareversions`
                   LEFT JOIN `glpi_softwares`
                     ON (`glpi_softwares`.`id` = `glpi_softwareversions`.`softwares_id`)
                   WHERE `glpi_softwares`.`id` IS NULL";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {
                  $query = "DELETE
                            FROM `glpi_softwareversions`
                            WHERE `id` = '".$data['id']."'";
                  $DB->query($query);
               }
            }
         }

         $query = "SELECT `glpi_softwares`.`id`,
                          `glpi_softwares`.`entities_id`,
                          `glpi_softwares`.`is_recursive`,
                          `glpi_softwareversions`.`id` AS vID
                   FROM `glpi_computers_softwareversions`
                   INNER JOIN `glpi_softwareversions`
                        ON (`glpi_computers_softwareversions`.`softwareversions_id`
                            = `glpi_softwareversions`.`id`)
                   INNER JOIN `glpi_softwares`
                        ON (`glpi_softwares`.`id` = `glpi_softwareversions`.`softwares_id`)
                   WHERE `glpi_computers_softwareversions`.`computers_id`
                        IN ".$this->item_search['Computer'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {

                  if ($data['is_recursive'] && in_array($data['entities_id'],
                                                        $to_entity_ancestors)) {
                     $this->addNotToBeTransfer('SoftwareVersion', $data['vID']);

                  } else {
                     $this->addToBeTransfer('SoftwareVersion', $data['vID']);
                  }
               }
            }
         }
      }

      // Software: From user choice only
      $this->item_search['Software'] =
               $this->createSearchConditionUsingArray($this->needtobe_transfer['Software']);
      $this->item_recurs['Software'] =
               $this->createSearchConditionUsingArray($this->noneedtobe_transfer['Software']);

      // Move license of software
      // TODO : should we transfer "affected license" ?
      $query = "SELECT `id`, `softwareversions_id_buy`, `softwareversions_id_use`
                FROM `glpi_softwarelicenses`
                WHERE `softwares_id` IN ".$this->item_search['Software'];

      foreach ($DB->request($query) AS $lic) {
         $this->addToBeTransfer('SoftwareLicense', $lic['id']);

         // Force version transfer (remove from item_recurs)
         if ($lic['softwareversions_id_buy']>0) {
            $this->addToBeTransfer('SoftwareVersion', $lic['softwareversions_id_buy']);
         }
         if ($lic['softwareversions_id_use']>0) {
            $this->addToBeTransfer('SoftwareVersion', $lic['softwareversions_id_use']);
         }
      }

      // Licenses: from softwares  and computers (affected)
      $this->item_search['SoftwareLicense'] =
            $this->createSearchConditionUsingArray($this->needtobe_transfer['SoftwareLicense']);
      $this->item_recurs['SoftwareLicense'] =
            $this->createSearchConditionUsingArray($this->noneedtobe_transfer['SoftwareLicense']);

      // Versions: from affected licenses and installed versions
      $this->item_search['SoftwareVersion'] =
            $this->createSearchConditionUsingArray($this->needtobe_transfer['SoftwareVersion']);
      $this->item_recurs['SoftwareVersion'] =
            $this->createSearchConditionUsingArray($this->noneedtobe_transfer['SoftwareVersion']);

      $this->item_search['NetworkEquipment'] =
            $this->createSearchConditionUsingArray($this->needtobe_transfer['NetworkEquipment']);

      // Tickets
      if ($this->options['keep_ticket']) {
         foreach ($this->TICKETS_TYPES as $itemtype) {
            if (isset($this->item_search[$itemtype])) {
               $query = "SELECT DISTINCT `id`
                         FROM `glpi_tickets`
                         WHERE `itemtype` = '$itemtype'
                               AND `items_id` IN ".$this->item_search[$itemtype];

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result)>0) {
                     while ($data=$DB->fetch_array($result)) {
                        $this->addToBeTransfer('Ticket', $data['id']);
                     }
                  }
               }
            }
         }
      }
      $this->item_search['Ticket'] =
               $this->createSearchConditionUsingArray($this->needtobe_transfer['Ticket']);

      // Contract : keep / delete + clean unused / keep unused
      if ($this->options['keep_contract']) {
         foreach ($this->CONTRACTS_TYPES as $itemtype) {
            if (isset($this->item_search[$itemtype])) {
               $itemtable = getTableForItemType($itemtype);

               // Clean DB
               $query = "SELECT `glpi_contracts_items`.`id`
                         FROM `glpi_contracts_items`
                         LEFT JOIN `$itemtable`
                           ON (`glpi_contracts_items`.`items_id` = `$itemtable`.`id`)
                         WHERE `glpi_contracts_items`.`itemtype` = '$itemtype'
                               AND `$itemtable`.`id` IS NULL";

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result)>0) {
                     while ($data=$DB->fetch_array($result)) {
                        $query = "DELETE
                                  FROM `glpi_contracts_items`
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                     }
                  }
               }
               // Clean DB
               $query = "SELECT `glpi_contracts_items`.`id`
                         FROM `glpi_contracts_items`
                         LEFT JOIN `glpi_contracts`
                           ON (`glpi_contracts_items`.`contracts_id` = `glpi_contracts`.`id`)
                         WHERE `glpi_contracts`.`id` IS NULL";

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result)>0) {
                     while ($data=$DB->fetch_array($result)) {
                        $query = "DELETE
                                  FROM `glpi_contracts_items`
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                     }
                  }
               }

               $query = "SELECT `contracts_id`,
                                `glpi_contracts`.`entities_id`,
                                `glpi_contracts`.`is_recursive`
                         FROM `glpi_contracts_items`
                         LEFT JOIN `glpi_contracts`
                               ON (`glpi_contracts_items`.`contracts_id` = `glpi_contracts`.`id`)
                         WHERE `glpi_contracts_items`.`itemtype` = '$itemtype'
                               AND `glpi_contracts_items`.`items_id`
                                    IN ".$this->item_search[$itemtype];

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result)>0) {
                     while ($data=$DB->fetch_array($result)) {

                        if ($data['is_recursive'] && in_array($data['entities_id'],
                                                              $to_entity_ancestors)) {
                           $this->addNotToBeTransfer('Contract', $data['contracts_id']);

                        } else {
                           $this->addToBeTransfer('Contract', $data['contracts_id']);
                        }

                     }
                  }
               }
            }
         }
      }
      $this->item_search['Contract'] =
               $this->createSearchConditionUsingArray($this->needtobe_transfer['Contract']);
      $this->item_recurs['Contract'] =
               $this->createSearchConditionUsingArray($this->noneedtobe_transfer['Contract']);
      // Supplier (depending of item link) / Contract - infocoms : keep / delete + clean unused / keep unused

      if ($this->options['keep_supplier']) {
         // Clean DB
         $query = "SELECT `glpi_contracts_suppliers`.`id`
                   FROM `glpi_contracts_suppliers`
                   LEFT JOIN `glpi_contracts`
                        ON (`glpi_contracts_suppliers`.`contracts_id` = `glpi_contracts`.`id`)
                   WHERE `glpi_contracts`.`id` IS NULL";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {
                  $query = "DELETE
                            FROM `glpi_contracts_suppliers`
                            WHERE `id` = '".$data['id']."'";
                  $DB->query($query);
               }
            }
         }
         // Clean DB
         $query = "SELECT `glpi_contracts_suppliers`.`id`
                   FROM `glpi_contracts_suppliers`
                   LEFT JOIN `glpi_suppliers`
                         ON (`glpi_contracts_suppliers`.`suppliers_id` = `glpi_suppliers`.`id`)
                   WHERE `glpi_suppliers`.`id` IS NULL";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {
                  $query = "DELETE
                            FROM `glpi_contracts_suppliers`
                            WHERE `id` = '".$data['id']."'";
                  $DB->query($query);
               }
            }
         }
         // Supplier Contract
         $query = "SELECT DISTINCT `suppliers_id`,
                                   `glpi_suppliers`.`is_recursive`,
                                   `glpi_suppliers`.`entities_id`
                   FROM `glpi_contracts_suppliers`
                   LEFT JOIN `glpi_suppliers`
                         ON (`glpi_suppliers`.`id` = `glpi_contracts_suppliers`.`suppliers_id`)
                   WHERE `contracts_id` IN ".$this->item_search['Contract'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {

                  if ($data['is_recursive'] && in_array($data['entities_id'],
                                                        $to_entity_ancestors)) {
                     $this->addNotToBeTransfer('Supplier', $data['suppliers_id']);

                  } else {
                     $this->addToBeTransfer('Supplier', $data['suppliers_id']);
                  }
               }
            }
         }
         // Ticket Supplier
         $query = "SELECT DISTINCT `suppliers_id_assign`,
                                   `glpi_suppliers`.`is_recursive`,
                                   `glpi_suppliers`.`entities_id`
                   FROM `glpi_tickets`
                   LEFT JOIN `glpi_suppliers`
                         ON (`glpi_suppliers`.`id` = `glpi_tickets`.`suppliers_id_assign`)
                   WHERE `suppliers_id_assign` > '0'
                         AND `glpi_tickets`.`id` IN ".$this->item_search['Ticket'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {

                  if ($data['is_recursive'] && in_array($data['entities_id'],
                                                        $to_entity_ancestors)) {
                     $this->addNotToBeTransfer('Supplier', $data['suppliers_id_assign']);

                  } else {
                     $this->addToBeTransfer('Supplier', $data['suppliers_id_assign']);
                  }

               }
            }
         }

         // Supplier infocoms
         if ($this->options['keep_infocom']) {
            foreach ($this->INFOCOMS_TYPES as $itemtype) {
               if (isset($this->item_search[$itemtype])) {
                  $itemtable = getTableForItemType($itemtype);
                  // Clean DB
                  $query = "SELECT `glpi_infocoms`.`id`
                            FROM `glpi_infocoms`
                            LEFT JOIN `$itemtable`
                               ON (`glpi_infocoms`.`items_id` = `$itemtable`.`id`)
                            WHERE `glpi_infocoms`.`itemtype` = '$itemtype'
                                  AND `$itemtable`.`id` IS NULL";

                  if ($result = $DB->query($query)) {
                     if ($DB->numrows($result)>0) {
                        while ($data=$DB->fetch_array($result)) {
                           $query = "DELETE
                                     FROM `glpi_infocoms`
                                     WHERE `id` = '".$data['id']."'";
                           $DB->query($query);
                        }
                     }
                  }
                  $query = "SELECT DISTINCT `suppliers_id`,
                                            `glpi_suppliers`.`is_recursive`,
                                            `glpi_suppliers`.`entities_id`
                            FROM `glpi_infocoms`
                            LEFT JOIN `glpi_suppliers`
                              ON (`glpi_suppliers`.`id` = `glpi_infocoms`.`suppliers_id`)
                            WHERE `suppliers_id` > '0'
                                  AND `itemtype` = '$itemtype'
                                  AND `items_id` IN ".$this->item_search[$itemtype];

                  if ($result = $DB->query($query)) {
                     if ($DB->numrows($result)>0) {
                        while ($data=$DB->fetch_array($result)) {

                           if ($data['is_recursive'] && in_array($data['entities_id'],
                                                                 $to_entity_ancestors)) {
                              $this->addNotToBeTransfer('Supplier', $data['suppliers_id']);

                           } else {
                              $this->addToBeTransfer('Supplier', $data['suppliers_id']);
                           }

                        }
                     }
                  }

               }
            }
         }

      }

      $this->item_search['Supplier'] =
               $this->createSearchConditionUsingArray($this->needtobe_transfer['Supplier']);
      $this->item_recurs['Supplier'] =
               $this->createSearchConditionUsingArray($this->noneedtobe_transfer['Supplier']);

      // Contact / Supplier : keep / delete + clean unused / keep unused
      if ($this->options['keep_contact']) {
         // Clean DB
         $query = "SELECT `glpi_contacts_suppliers`.`id`
                   FROM `glpi_contacts_suppliers`
                   LEFT JOIN `glpi_contacts`
                         ON (`glpi_contacts_suppliers`.`contacts_id` = `glpi_contacts`.`id`)
                   WHERE `glpi_contacts`.`id` IS NULL";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {
                  $query = "DELETE
                            FROM `glpi_contacts_suppliers`
                            WHERE `id` = '".$data['id']."'";
                  $DB->query($query);
               }
            }
         }

         // Clean DB
         $query = "SELECT `glpi_contacts_suppliers`.`id`
                   FROM `glpi_contacts_suppliers`
                   LEFT JOIN `glpi_suppliers`
                         ON (`glpi_contacts_suppliers`.`suppliers_id` = `glpi_suppliers`.`id`)
                   WHERE `glpi_suppliers`.`id` IS NULL";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {
                  $query = "DELETE
                            FROM `glpi_contacts_suppliers`
                            WHERE `id` = '".$data['id']."'";
                  $DB->query($query);
               }
            }
         }

         // Supplier Contact
         $query = "SELECT DISTINCT `contacts_id`,
                                   `glpi_contacts`.`is_recursive`,
                                   `glpi_contacts`.`entities_id`
                   FROM `glpi_contacts_suppliers`
                   LEFT JOIN `glpi_contacts`
                        ON (`glpi_contacts`.`id` = `glpi_contacts_suppliers`.`contacts_id`)
                   WHERE `suppliers_id` IN ".$this->item_search['Supplier'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {

                  if ($data['is_recursive'] && in_array($data['entities_id'],
                                                        $to_entity_ancestors)) {
                     $this->addNotToBeTransfer('Contact', $data['contacts_id']);

                  } else {
                     $this->addToBeTransfer('Contact', $data['contacts_id']);
                  }

               }
            }
         }

      }

      $this->item_search['Contact'] =
               $this->createSearchConditionUsingArray($this->needtobe_transfer['Contact']);
      $this->item_recurs['Contact'] =
               $this->createSearchConditionUsingArray($this->noneedtobe_transfer['Contact']);

      // Document : keep / delete + clean unused / keep unused
      if ($this->options['keep_document']) {
         foreach ($this->DOCUMENTS_TYPES as $itemtype) {
            if (isset($this->item_search[$itemtype])) {
               $itemtable = getTableForItemType($itemtype);
               // Clean DB
               $query = "SELECT `glpi_documents_items`.`id`
                         FROM `glpi_documents_items`
                         LEFT JOIN `$itemtable`
                           ON (`glpi_documents_items`.`items_id` = `$itemtable`.`id`)
                         WHERE `glpi_documents_items`.`itemtype` = '$itemtype'
                               AND `$itemtable`.`id` IS NULL";

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result)>0) {
                     while ($data=$DB->fetch_array($result)) {
                        $query = "DELETE
                                  FROM `glpi_documents_items`
                                  WHERE `id` = '".$data['id']."'";
                        $DB->query($query);
                     }
                  }
               }

               $query = "SELECT `documents_id`, `glpi_documents`.`is_recursive`,
                                `glpi_documents`.`entities_id`
                         FROM `glpi_documents_items`
                         LEFT JOIN `glpi_documents`
                              ON (`glpi_documents`.`id` = `glpi_documents_items`.`documents_id`)
                         WHERE `itemtype` = '$itemtype'
                               AND `items_id` IN ".$this->item_search[$itemtype];

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result)>0) {
                     while ($data=$DB->fetch_array($result)) {

                        if ($data['is_recursive'] && in_array($data['entities_id'],
                                                              $to_entity_ancestors)) {
                           $this->addNotToBeTransfer('Document', $data['documents_id']);

                        } else {
                           $this->addToBeTransfer('Document', $data['documents_id']);
                        }

                     }
                  }
               }

            }
         }
      }

      $this->item_search['Document'] =
               $this->createSearchConditionUsingArray($this->needtobe_transfer['Document']);
      $this->item_recurs['Document'] =
               $this->createSearchConditionUsingArray($this->noneedtobe_transfer['Document']);

      // printer -> cartridges : keep / delete + clean
      if ($this->options['keep_cartridgeitem']) {
         if (isset($this->item_search['Printer'])) {
            $query = "SELECT `cartridgeitems_id`
                      FROM `glpi_cartridges`
                      WHERE `printers_id` IN ".$this->item_search['Printer'];

            if ($result = $DB->query($query)) {
               if ($DB->numrows($result)>0) {
                  while ($data=$DB->fetch_array($result)) {
                     $this->addToBeTransfer('CartridgeItem',$data['cartridgeitems_id']);
                  }
               }
            }

         }
      }

      $this->item_search['CartridgeItem'] =
               $this->createSearchConditionUsingArray($this->needtobe_transfer['CartridgeItem']);

      // Init all item_search if not defined
      foreach ($types as $itemtype) {
         if (!isset($this->item_search[$itemtype])) {
            $this->item_search[$itemtype]="(-1)";
         }
      }

   }


   /**
    * Create IN condition for SQL requests based on a array if ID
    *
    * @param $array array of ID
    *
    * @return string of the IN condition
   **/
   function createSearchConditionUsingArray($array) {

      if (is_array($array) && count($array)) {
         return "('".implode("','",$array)."')";
      }
      return "(-1)";
   }


   /**
    * transfer an item to another item (may be the same) in the new entity
    *
    * @param $itemtype item type to transfer
    * @param $ID ID of the item to transfer
    * @param $newID new ID of the ite
    *
    * Transfer item to a new Item if $ID==$newID : only update entities_id field : $ID!=$new ID -> copy datas (like template system)
    * @return nothing (diplays)
   **/
   function transferItem($itemtype, $ID, $newID) {
      global $CFG_GLPI, $DB;

      if (!class_exists($itemtype)) {
         return;
      }
      $item = new $itemtype();

      // Is already transfer ?
      if (!isset($this->already_transfer[$itemtype][$ID])) {
         // Check computer exists ?
         if ($item->getFromDB($newID)) {
            // Manage Ocs links
            $dataocslink  = array();
            $ocs_computer = false;

            if ($itemtype == 'Computer' && $CFG_GLPI['use_ocs_mode']) {
               $query = "SELECT *
                         FROM `glpi_ocslinks`
                         WHERE `computers_id` = '$ID'";

               if ($result=$DB->query($query)) {
                  if ($DB->numrows($result)>0) {
                     $dataocslink  = $DB->fetch_assoc($result);
                     $ocs_computer = true;
                  }
               }

            }

            // Network connection ? keep connected / keep_disconnected / delete
            if (in_array($itemtype, array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral',
                                          'Phone', 'Printer'))) {
               $this->transferNetworkLink($itemtype, $ID, $newID, $ocs_computer);
            }

            // Device : keep / delete : network case : delete if net connection delete in ocs case
            if (in_array($itemtype,array('Computer'))) {
               $this->transferDevices($itemtype,$ID,$ocs_computer);
            }

            // Reservation : keep / delete
            if (in_array($itemtype,$CFG_GLPI["reservation_types"])) {
               $this->transferReservations($itemtype, $ID, $newID);
            }

            // History : keep / delete
            $this->transferHistory($itemtype, $ID, $newID);
            // Ticket : delete / keep and clean ref / keep and move
            $this->transferTickets($itemtype, $ID, $newID);
            // Infocoms : keep / delete

            if (in_array($itemtype,$this->INFOCOMS_TYPES)) {
               $this->transferInfocoms($itemtype, $ID, $newID);
            }

            if ($itemtype == 'Software') {
               $this->transferSoftwareLicensesAndVersions($ID);
            }

            if ($itemtype == 'Computer') {
               // Monitor Direct Connect : keep / delete + clean unused / keep unused
               $this->transferDirectConnection($itemtype, $ID, 'Monitor', $ocs_computer);
               // Peripheral Direct Connect : keep / delete + clean unused / keep unused
               $this->transferDirectConnection($itemtype, $ID, 'Peripheral', $ocs_computer);
               // Phone Direct Connect : keep / delete + clean unused / keep unused
               $this->transferDirectConnection($itemtype, $ID, 'Phone');
               // Printer Direct Connect : keep / delete + clean unused / keep unused
               $this->transferDirectConnection($itemtype, $ID, 'Printer', $ocs_computer);
               // Licence / Software :  keep / delete + clean unused / keep unused
               $this->transferComputerSoftwares($ID, $ocs_computer);
               // Computer Disks :  delete them or not ?
               $this->transferComputerDisks($ID);
            }

            // Computer Direct Connect : delete link if it is the initial transfer item (no recursion)
            if ($this->inittype==$itemtype && in_array($itemtype, array('Monitor', 'Phone',
                                                                        'Peripheral', 'Printer'))) {
               $this->deleteDirectConnection($itemtype, $ID);
            }

            // Contract : keep / delete + clean unused / keep unused
            if (in_array($itemtype,$this->CONTRACTS_TYPES)) {
               $this->transferContracts($itemtype, $ID, $newID);
            }

            // Contact / Supplier : keep / delete + clean unused / keep unused
            if ($itemtype == 'Supplier') {
               $this->transferSupplierContacts($ID, $newID);
            }

            // Document : keep / delete + clean unused / keep unused
            if (in_array($itemtype,$this->DOCUMENTS_TYPES)) {
               $this->transferDocuments($itemtype, $ID, $newID);
            }

            // transfer compatible printers
            if ($itemtype == 'CartridgeItem') {
               $this->transferCompatiblePrinters($ID, $newID);
            }

            // Cartridges  and cartridges items linked to printer
            if ($itemtype == 'Printer') {
               $this->transferPrinterCartridges($ID, $newID);
            }

            // Transfer Item
            $input = array('id'          => $newID,
                           'entities_id' => $this->to);

            // Manage Location dropdown
            if (isset($item->fields['locations_id'])) {
               $input['locations_id'] =
                                 $this->transferDropdownLocation($item->fields['locations_id']);
            }

            if ($itemtype == 'Ticket') {
               $input2 = $this->transferTicketAdditionalInformations($item->fields);
               $input  = array_merge($input,$input2);
               $this->transferTicketTaskCategory($ID, $newID);
            }

            $item->update($input);
            $this->addToAlreadyTransfer($itemtype,$ID,$newID);
            doHook("item_transfer", array('type'  => $itemtype,
                                          'id'    => $ID,
                                          'newID' => $newID));
         }
      }
   }


   /**
    * Add an item to already transfer array
    *
    * @param $itemtype item type
    * @param $ID item original ID
    * @param $newID item new ID
   **/
   function addToAlreadyTransfer($itemtype,$ID,$newID) {

      if (!isset($this->already_transfer[$itemtype])) {
         $this->already_transfer[$itemtype] = array();
      }
      $this->already_transfer[$itemtype][$ID]=$newID;
   }


   /**
    * Transfer location
    *
    * @param $locID location ID
    *
    * @return new location ID
   **/
   function transferDropdownLocation($locID) {
      global $DB;

      if ($locID>0) {
         if (isset($this->already_transfer['locations_id'][$locID])) {
            return $this->already_transfer['locations_id'][$locID];

         } else { // Not already transfer
            // Search init item
            $query = "SELECT *
                      FROM `glpi_locations`
                      WHERE `id` = '$locID'";

            if ($result=$DB->query($query)) {
               if ($DB->numrows($result)) {
                  $data = $DB->fetch_assoc($result);
                  $data = addslashes_deep($data);

                  $input['entities_id']  = $this->to;
                  $input['completename'] = $data['completename'];
                  $location = new Location();
                  $newID    = $location->findID($input);

                  if ($newID<0) {
                     $newID = $location->import($input);
                  }

                  $this->addToAlreadyTransfer('locations_id', $locID, $newID);
                  return $newID;
               }
            }

         }

      }
      return 0;
   }


   /**
    * Transfer netpoint
    *
    * @param $netpoints_id netpoint ID
    *
    * @return new netpoint ID
   **/
   function transferDropdownNetpoint($netpoints_id) {
      global $DB;

      if ($netpoints_id>0) {
         if (isset($this->already_transfer['netpoints_id'][$netpoints_id])) {
            return $this->already_transfer['netpoints_id'][$netpoints_id];

         } else { // Not already transfer
            // Search init item
            $query = "SELECT *
                      FROM `glpi_netpoints`
                      WHERE `id` = '$netpoints_id'";

            if ($result=$DB->query($query)) {
               if ($DB->numrows($result)) {
                  $data  = $DB->fetch_array($result);
                  $data  = addslashes_deep($data);
                  $locID = $this->transferDropdownLocation($data['locations_id']);

                  // Search if the locations_id already exists in the destination entity
                  $query = "SELECT `id`
                            FROM `glpi_netpoints`
                            WHERE `entities_id` = '".$this->to."'
                                  AND `name` = '".$data['name']."'
                                  AND `locations_id` = '$locID'";

                  if ($result_search=$DB->query($query)) {
                     // Found : -> use it
                     if ($DB->numrows($result_search)>0) {
                        $newID = $DB->result($result_search,0,'id');
                        $this->addToAlreadyTransfer('netpoints_id', $netpoints_id, $newID);
                        return $newID;
                     }
                  }

                  // Not found :
                  // add item
                  $netpoint = new Netpoint();
                  $newID = $netpoint->add(array('name'         => $data['name'],
                                                'comment'      => $data['comment'],
                                                'entities_id'  => $this->to,
                                                'locations_id' => $locID));

                  $this->addToAlreadyTransfer('netpoints_id', $netpoints_id, $newID);
                  return $newID;
               }
            }

         }

      }
      return 0;
   }


   /**
    * Transfer cartridges of a printer
    *
    * @param $ID original ID of the printer
    * @param $newID new ID of the printer
   **/
   function transferPrinterCartridges($ID,$newID) {
      global $DB;

      // Get cartrdiges linked
      $query = "SELECT *
                FROM `glpi_cartridges`
                WHERE `glpi_cartridges`.`printers_id` = '$ID'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $cart     = new Cartridge();
            $carttype = new CartridgeItem();

            while ($data=$DB->fetch_array($result)) {
               $need_clean_process = false;

               // Foreach cartridges
               // if keep
               if ($this->options['keep_cartridgeitem']) {
                  $newcartID     =- 1;
                  $newcarttypeID = -1;

                  // 1 - Search carttype destination ?
                  // Already transfer carttype :
                  if (isset($this->already_transfer['CartridgeItem'][$data['cartridgeitems_id']])) {
                     $newcarttypeID
                           = $this->already_transfer['CartridgeItem'][$data['cartridgeitems_id']];

                  } else {
                     // Not already transfer cartype
                     $query = "SELECT count(*) AS CPT
                               FROM `glpi_cartridges`
                               WHERE `glpi_cartridges`.`cartridgeitems_id`
                                          = '".$data['cartridgeitems_id']."'
                                     AND `glpi_cartridges`.`printers_id` > '0'
                                     AND `glpi_cartridges`.`printers_id`
                                          NOT IN ".$this->item_search['Printer'];
                     $result_search = $DB->query($query);

                     // Is the carttype will be completly transfer ?
                     if ($DB->result($result_search,0,'CPT')==0) {
                        // Yes : transfer
                        $need_clean_process = false;
                        $this->transferItem('CartridgeItem', $data['cartridgeitems_id'],
                                            $data['cartridgeitems_id']);
                        $newcarttypeID = $data['cartridgeitems_id'];

                     } else {
                        // No : copy carttype
                        $need_clean_process = true;
                        $carttype->getFromDB($data['cartridgeitems_id']);
                        // Is existing carttype in the destination entity ?
                        $query = "SELECT *
                                  FROM `glpi_cartridgeitems`
                                  WHERE `entities_id` = '".$this->to."'
                                        AND `name` = '".addslashes($carttype->fields['name'])."'";

                        if ($result_search=$DB->query($query)) {
                           if ($DB->numrows($result_search)>0) {
                              $newcarttypeID = $DB->result($result_search,0,'id');
                           }
                        }

                        // Not found -> transfer copy
                        if ($newcarttypeID<0) {
                           // 1 - create new item
                           unset($carttype->fields['id']);
                           $input = $carttype->fields;
                           $input['entities_id'] = $this->to;
                           unset($carttype->fields);
                           $newcarttypeID = $carttype->add($input);
                           // 2 - transfer as copy
                           $this->transferItem('CartridgeItem', $data['cartridgeitems_id'],
                                               $newcarttypeID);
                        }

                        // Founded -> use to link : nothing to do
                     }

                  }

                  // Update cartridge if needed
                  if ($newcarttypeID>0 && $newcarttypeID!=$data['cartridgeitems_id']) {
                     $cart->update(array('id'                 => $data['id'],
                                         'cartridgeitems_id' => $newcarttypeID));
                  }

               } else { // Do not keep
                  // If same printer : delete cartridges
                  if ($ID==$newID) {
                     $del_query = "DELETE
                                   FROM `glpi_cartridges`
                                   WHERE `printers_id` = '$ID'";
                     $DB->query($del_query);
                  }
                  $need_clean_process = true;
               }

               // CLean process
               if ($need_clean_process && $this->options['clean_cartridgeitem']) {
                  // Clean carttype
                  $query2 = "SELECT COUNT(*) AS CPT
                             FROM `glpi_cartridges`
                             WHERE `cartridgeitems_id` = '" . $data['cartridgeitems_id'] . "'";
                  $result2 = $DB->query($query2);

                  if ($DB->result($result2, 0, 'CPT') == 0) {
                     if ($this->options['clean_cartridgeitem']==1) { // delete
                        $carttype->delete(array('id' => $data['cartridgeitems_id']));
                     }
                     if ($this->options['clean_cartridgeitem']==2) { // purge
                        $carttype->delete(array('id' => $data['cartridgeitems_id']),1);
                     }
                  }
               }

            }

         }
      }

   }


   /**
    * Copy (if needed) One software to the destination entity
    *
    * @param $ID of the software
    *
    * @return $ID of the new software (could be the same)
   **/
   function copySingleSoftware ($ID) {
      global $DB;

      if (isset($this->already_transfer['Software'][$ID])) {
         return $this->already_transfer['Software'][$ID];
      }

      $soft = new Software();
      if ($soft->getFromDB($ID)) {

         if ($soft->fields['is_recursive']
             && in_array($soft->fields['entities_id'], getAncestorsOf("glpi_entities",
                                                                      $this->to))) {
            // no need to copy
            $newsoftID = $ID;

         } else {
            $query = "SELECT *
                      FROM `glpi_softwares`
                      WHERE `entities_id` = ".$this->to."
                            AND `name` = '".addslashes($soft->fields['name'])."'";

            if ($data=$DB->request($query)->next()) {
               $newsoftID = $data["id"];

            } else {
               // create new item (don't check if move possible => clean needed)
               unset($soft->fields['id']);
               $input = $soft->fields;
               $input['entities_id'] = $this->to;
               unset($soft->fields);
               $newsoftID = $soft->add($input);
            }

         }

         $this->addToAlreadyTransfer('Software', $ID, $newsoftID);
         return $newsoftID;
      }

      return -1;
   }


   /**
    * Copy (if needed) One softwareversion to the Dest Entity
    *
    * @param $ID of the version
    *
    * @return $ID of the new version (could be the same)
   **/
   function copySingleVersion ($ID) {
      global $DB;

      if (isset($this->already_transfer['SoftwareVersion'][$ID])) {
         return $this->already_transfer['SoftwareVersion'][$ID];
      }

      $vers = new SoftwareVersion();
      if ($vers->getFromDB($ID)) {
         $newsoftID = $this->copySingleSoftware($vers->fields['softwares_id']);

         if ($newsoftID == $vers->fields['softwares_id']) {
            // no need to copy
            $newversID = $ID;

         } else {
            $query = "SELECT `id`
                      FROM `glpi_softwareversions`
                      WHERE `softwares_id` = $newsoftID
                            AND `name` = '".addslashes($vers->fields['name'])."'";

            if ($data=$DB->request($query)->next()) {
               $newversID = $data["id"];

            } else {
               // create new item (don't check if move possible => clean needed)
               unset($vers->fields['id']);
               $input = $vers->fields;
               unset($vers->fields);
               // entities_id and is_recursive from new software are set in prepareInputForAdd
               $input['softwares_id'] = $newsoftID;
               $newversID=$vers->add($input);
            }

         }

         $this->addToAlreadyTransfer('SoftwareVersion', $ID, $newversID);
         return $newversID;
      }

      return -1;
   }


   /**
    * Transfer disks of a computer
    *
    * @param $ID ID of the computer
   **/
   function transferComputerDisks($ID) {

      if (!$this->options['keep_disk']) {
         $disk = new ComputerDisk();
         $disk->cleanDBonItemDelete('Computer', $ID);
      }
   }


   /**
    * Transfer softwares of a computer
    *
    * @param $ID ID of the computer
    * @param $ocs_computer ID of the computer in OCS if imported from OCS
   **/
   function transferComputerSoftwares($ID, $ocs_computer=false) {
      global $DB;

      // Get Installed version
      $query = "SELECT *
                FROM `glpi_computers_softwareversions`
                WHERE `computers_id` = '$ID'
                      AND `softwareversions_id` NOT IN ".$this->item_recurs['SoftwareVersion'];

      foreach ($DB->request($query) AS $data) {
         if ($this->options['keep_software']) {
            $newversID = $this->copySingleVersion($data['softwareversions_id']);

            if ($newversID>0 && $newversID!=$data['softwareversions_id']) {
               $query = "UPDATE `glpi_computers_softwareversions`
                         SET `softwareversions_id` = '$newversID'
                         WHERE `id` = ".$data['id'];
               $DB->query($query);
            }

         } else { // Do not keep
            // Delete inst software for computer
            $del_query = "DELETE
                          FROM `glpi_computers_softwareversions`
                          WHERE `id` = ".$data['id'];
            $DB->query($del_query);
         }
      } // each installed version

      // Affected licenses
      if ($this->options['keep_software']) {
         $query = "SELECT `id`
                   FROM `glpi_computers_softwarelicenses`
                   WHERE `computers_id` = '$ID'";
         foreach ($DB->request($query) AS $data) {
            $this->transferAffectedLicense($data['id']);
         }
      } else {
         if ($ocs_computer) {
            $query = "UPDATE `glpi_ocslinks`
                      SET `import_software` = NULL
                      WHERE `computers_id` = '$ID'";
            $DB->query($query);
         }
         $query = "DELETE
                   FROM `glpi_computers_softwarelicenses`
                   WHERE `computers_id` = '$ID'";
         $DB->query($query);
      }
   }


   /**
    * Transfer affected licenses to a computer
    *
    * @param $ID ID of the License
   **/
   function transferAffectedLicense($ID) {
      global $DB;

      $computer_softwarelicense = new Computer_SoftwareLicense();
      $license                  = new SoftwareLicense();

      if ($computer_softwarelicense->getFromDB($ID)) {
         if ($license->getFromDB($computer_softwarelicense->getField('softwarelicenses_id'))) {

            //// Update current : decrement number by 1 if valid
            if ($license->getField('number')>1) {
               $license->update(array('id'     => $license->getID(),
                                      'number' => ($license->getField('number')-1)));
            }

            // Create new license : need to transfer softwre and versions before
            $input     = array();
            $newsoftID = $this->copySingleSoftware($license->fields['softwares_id']);

            if ($newsoftID > 0) {
               //// If license already exists : increment number by one
               $query = "SELECT *
                         FROM `glpi_softwarelicenses`
                         WHERE `softwares_id` = '$newsoftID'
                               AND `name` = '".addslashes($license->fields['name'])."'
                               AND `serial` = '".addslashes($license->fields['serial'])."'";

               $newlicID = -1;
               if ($result=$DB->query($query)) {
                  //// If exists : increment number by 1
                  if ($DB->numrows($result)>0) {
                     $data     = $DB->fetch_array($result);
                     $newlicID = $data['id'];
                     $license->update(array('id'     => $data['id'],
                                            'number' => $data['number']+1));

                  } else {
                     //// If not exists : create with number = 1
                     $input = $license->fields;
                     foreach (array('softwareversions_id_buy', 'softwareversions_id_use') as $field) {
                        if ($license->fields[$field]>0) {
                           $newversID = $this->copySingleVersion($license->fields[$field]);
                           if ($newversID>0 && $newversID!=$license->fields[$field]) {
                              $input[$field] = $newversID;
                           }
                        }
                     }

                     unset($input['id']);
                     $input['number']       = 1;
                     $input['entities_id']  = $this->to;
                     $input['softwares_id'] = $newsoftID;
                     $newlicID              = $license->add($input);
                  }
               }

               if ($newlicID>0) {
                  $input = array('id'                  => $ID,
                                 'softwarelicenses_id' => $newlicID);
                  $computer_softwarelicense->update($input);
               }
            }
         }
      } // getFromDB

   }


   /**
    * Transfer License and Version of a Software
    *
    * @param $ID ID of the Software
   **/
   function transferSoftwareLicensesAndVersions($ID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_softwarelicenses`
                WHERE `softwares_id` = '$ID'";

      foreach ($DB->request($query) AS $data) {
         $this->transferItem('SoftwareLicense', $data['id'], $data['id']);
      }

      $query = "SELECT `id`
                FROM `glpi_softwareversions`
                WHERE `softwares_id` = '$ID'";

      foreach ($DB->request($query) AS $data) {
         // Just Store the info.
         $this->addToAlreadyTransfer('SoftwareVersion', $data['id'], $data['id']);
      }
   }


   function cleanSoftwareVersions() {

      if (!isset($this->already_transfer['SoftwareVersion'])) {
         return;
      }

      $vers = new SoftwareVersion();
      foreach ($this->already_transfer['SoftwareVersion'] AS $old => $new) {
         if (countElementsInTable("glpi_softwarelicenses", "softwareversions_id_buy=$old")==0
             && countElementsInTable("glpi_softwarelicenses", "softwareversions_id_use=$old")==0
             && countElementsInTable("glpi_computers_softwareversions",
                                     "softwareversions_id=$old")==0) {

            $vers->delete(array('id' => $old));
         }
      }
   }


   function cleanSoftwares() {

      if (!isset($this->already_transfer['Software'])) {
         return;
      }

      $soft = new Software();
      foreach ($this->already_transfer['Software'] AS $old => $new) {
         if (countElementsInTable("glpi_softwarelicenses", "softwares_id=$old")==0
             && countElementsInTable("glpi_softwareversions", "softwares_id=$old")==0) {

            if ($this->options['clean_software']==1) { // delete
               $soft->delete(array('id' => $old),0);

            } else if ($this->options['clean_software']==2) { // purge
               $soft->delete(array('id' => $old),1);
            }

         }
      }

   }


   /**
    * Transfer contracts
    *
    * @param $itemtype original type of transfered item
    * @param $ID original ID of the contract
    * @param $newID new ID of the contract
   **/
   function transferContracts($itemtype, $ID, $newID) {
      global $DB;

      $need_clean_process = false;

      // if keep
      if ($this->options['keep_contract']) {
         $contract = new Contract();
         // Get contracts for the item
         $query = "SELECT *
                   FROM `glpi_contracts_items`
                   WHERE `items_id` = '$ID'
                         AND `itemtype` = '$itemtype'
                         AND `contracts_id` NOT IN ".$this->item_recurs['Contract'];

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               // Foreach get item

               while ($data=$DB->fetch_array($result)) {
                  $need_clean_process = false;
                  $item_ID            = $data['contracts_id'];
                  $newcontractID      = -1;

                  // is already transfer ?
                  if (isset($this->already_transfer['Contract'][$item_ID])) {
                     $newcontractID = $this->already_transfer['Contract'][$item_ID];
                     if ($newcontractID != $item_ID) {
                        $need_clean_process = true;
                     }

                  } else {
                     // No
                     // Can be transfer without copy ? = all linked items need to be transfer (so not copy)
                     $canbetransfer = true;
                     $query = "SELECT DISTINCT `itemtype`
                               FROM `glpi_contracts_items`
                               WHERE `contracts_id` = '$item_ID'";

                     if ($result_type = $DB->query($query)) {
                        if ($DB->numrows($result_type)>0) {
                           while (($data_type=$DB->fetch_array($result_type)) && $canbetransfer) {
                              $dtype = $data_ty                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           