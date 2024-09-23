<?php
namespace verbb\events\migrations;

use verbb\events\elements\Event;
use verbb\events\elements\LegacyTicket;
use verbb\events\elements\LegacyTicketType;
use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
use verbb\events\fieldlayoutelements as LayoutFields;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;
use craft\migrations\BaseContentRefactorMigration;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

use Exception;
use ReflectionClass;

use yii\db\Expression;

class m240921_000000_events3 extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->update(Table::ELEMENTS, ['type' => LegacyTicket::class], ['type' => Ticket::class]);
        $this->update(Table::ELEMENTS, ['type' => LegacyTicketType::class], ['type' => TicketType::class]);

        $this->update(Table::FIELDLAYOUTS, ['type' => LegacyTicket::class], ['type' => Ticket::class]);
        $this->update(Table::FIELDLAYOUTS, ['type' => LegacyTicketType::class], ['type' => TicketType::class]);

        // Mark as legacy for now
        if (!$this->db->tableExists('{{%events_legacy_ticket_types}}')) {
            $this->renameTable('{{%events_tickettypes}}', '{{%events_legacy_ticket_types}}');

            MigrationHelper::dropAllForeignKeysToTable('{{%events_legacy_ticket_types}}', $this);
            MigrationHelper::dropAllForeignKeysOnTable('{{%events_legacy_ticket_types}}', $this);
            MigrationHelper::dropAllIndexesOnTable('{{%events_legacy_ticket_types}}', $this);
        }

        if (!$this->db->tableExists('{{%events_legacy_tickets}}')) {
            $this->renameTable('{{%events_tickets}}', '{{%events_legacy_tickets}}');

            MigrationHelper::dropAllForeignKeysToTable('{{%events_legacy_tickets}}', $this);
            MigrationHelper::dropAllForeignKeysOnTable('{{%events_legacy_tickets}}', $this);
            MigrationHelper::dropAllIndexesOnTable('{{%events_legacy_tickets}}', $this);
        }

        // Rename tables and make any schema changes for events data
        if (!$this->db->tableExists('{{%events_event_types}}')) {
            $this->renameTable('{{%events_eventtypes}}', '{{%events_event_types}}');
        }

        if (!$this->db->tableExists('{{%events_event_types_sites}}')) {
            $this->renameTable('{{%events_eventtypes_sites}}', '{{%events_event_types_sites}}');
        }

        if (!$this->db->tableExists('{{%events_purchased_tickets}}')) {
            $this->renameTable('{{%events_purchasedtickets}}', '{{%events_purchased_tickets}}');
        }

        // Add new columns
        if (!$this->db->columnExists('{{%events_events}}', 'ticketsCache')) {
            $this->addColumn('{{%events_events}}', 'ticketsCache', $this->string()->after('expiryDate'));
        }


        if (!$this->db->columnExists('{{%events_event_types}}', 'sessionFieldLayoutId')) {
            $this->addColumn('{{%events_event_types}}', 'sessionFieldLayoutId', $this->integer()->after('fieldLayoutId'));
        }

        if (!$this->db->columnExists('{{%events_event_types}}', 'ticketTypeFieldLayoutId')) {
            $this->addColumn('{{%events_event_types}}', 'ticketTypeFieldLayoutId', $this->integer()->after('sessionFieldLayoutId'));
        }

        if (!$this->db->columnExists('{{%events_event_types}}', 'enableVersioning')) {
            $this->addColumn('{{%events_event_types}}', 'enableVersioning', $this->boolean()->defaultValue(false)->after('handle'));
        }

        if (!$this->db->columnExists('{{%events_event_types}}', 'sessionTitleFormat')) {
            $this->addColumn('{{%events_event_types}}', 'sessionTitleFormat', $this->string()->after('enableVersioning'));
        }

        if (!$this->db->columnExists('{{%events_event_types}}', 'ticketTitleFormat')) {
            $this->addColumn('{{%events_event_types}}', 'ticketTitleFormat', $this->string()->after('sessionTitleFormat'));
        }

        if (!$this->db->columnExists('{{%events_event_types}}', 'ticketSkuFormat')) {
            $this->addColumn('{{%events_event_types}}', 'ticketSkuFormat', $this->string()->after('ticketTitleFormat'));
        }

        if (!$this->db->columnExists('{{%events_event_types}}', 'purchasedTicketTitleFormat')) {
            $this->addColumn('{{%events_event_types}}', 'purchasedTicketTitleFormat', $this->string()->after('ticketSkuFormat'));
        }


        if (!$this->db->columnExists('{{%events_event_types_sites}}', 'template')) {
            $this->addColumn('{{%events_event_types_sites}}', 'template', $this->string(500)->after('uriFormat'));
        }

        if (!$this->db->columnExists('{{%events_event_types_sites}}', 'enabledByDefault')) {
            $this->addColumn('{{%events_event_types_sites}}', 'enabledByDefault', $this->boolean()->defaultValue(true)->notNull()->after('hasUrls'));
        }


        if (!$this->db->columnExists('{{%events_purchased_tickets}}', 'sessionId')) {
            $this->addColumn('{{%events_purchased_tickets}}', 'sessionId', $this->integer()->after('eventId'));
        }

        if (!$this->db->columnExists('{{%events_purchased_tickets}}', 'ticketTypeId')) {
            $this->addColumn('{{%events_purchased_tickets}}', 'ticketTypeId', $this->integer()->after('ticketId'));
        }

        if (!$this->db->columnExists('{{%events_purchased_tickets}}', 'legacyTicketId')) {
            $this->addColumn('{{%events_purchased_tickets}}', 'legacyTicketId', $this->string()->after('checkedInDate'));
        }


        if (!$this->db->tableExists('{{%events_sessions}}')) {
            $this->createTable('{{%events_sessions}}', [
                'id' => $this->primaryKey(),
                'primaryOwnerId' => $this->integer(),
                'startDate' => $this->dateTime(),
                'endDate' => $this->dateTime(),
                'allDay' => $this->boolean(),
                'groupUid' => $this->char(36),
                'deletedWithEvent' => $this->boolean()->notNull()->defaultValue(false),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists('{{%events_ticket_types}}')) {
            $this->createTable('{{%events_ticket_types}}', [
                'id' => $this->primaryKey(),
                'primaryOwnerId' => $this->integer(),
                'price' => $this->decimal(14, 4),
                'capacity' => $this->integer(),
                'availableFrom' => $this->dateTime(),
                'availableTo' => $this->dateTime(),
                'deletedWithEvent' => $this->boolean()->notNull()->defaultValue(false),
                'legacyTicketId' => $this->string(),
                'legacyTicketTypeId' => $this->string(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists('{{%events_tickets}}')) {
            $this->createTable('{{%events_tickets}}', [
                'id' => $this->primaryKey(),
                'eventId' => $this->integer(),
                'sessionId' => $this->integer(),
                'typeId' => $this->integer(),
                'deletedWithEvent' => $this->boolean()->notNull()->defaultValue(false),
                'deletedWithSession' =>$this->boolean()->notNull()->defaultValue(false),
                'deletedWithType' => $this->boolean()->notNull()->defaultValue(false),
                'legacyTicketId' => $this->string(),
                'legacyTicketTypeId' => $this->string(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        // Move columns to legacy
        $this->update('{{%events_purchased_tickets}}', ['legacyTicketId' => new Expression('ticketId')]);
        $this->update('{{%events_purchased_tickets}}', ['ticketId' => null]);

        // Setup Indexes
        $this->createIndex(null, '{{%events_event_types}}', 'sessionFieldLayoutId', false);
        $this->createIndex(null, '{{%events_event_types}}', 'ticketTypeFieldLayoutId', false);

        $this->createIndex(null, '{{%events_purchased_tickets}}', 'sessionId', false);
        $this->createIndex(null, '{{%events_purchased_tickets}}', 'ticketTypeId', false);

        $this->createIndex(null, '{{%events_sessions}}', 'primaryOwnerId', false);

        $this->createIndex(null, '{{%events_ticket_types}}', 'primaryOwnerId', false);

        $this->createIndex(null, '{{%events_tickets}}', 'eventId', false);
        $this->createIndex(null, '{{%events_tickets}}', 'sessionId', false);
        $this->createIndex(null, '{{%events_tickets}}', 'typeId', false);

        // Setup Foreign Keys
        $this->addForeignKey(null, '{{%events_event_types}}', 'sessionFieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_event_types}}', 'ticketTypeFieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);

        $this->addForeignKey(null, '{{%events_purchased_tickets}}', 'sessionId', '{{%events_sessions}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_purchased_tickets}}', 'ticketId', '{{%events_tickets}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_purchased_tickets}}', 'ticketTypeId', '{{%events_ticket_types}}', 'id', 'SET NULL', null);

        $this->addForeignKey(null, '{{%events_sessions}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_sessions}}', 'primaryOwnerId', '{{%events_events}}', 'id', 'CASCADE', null);

        $this->addForeignKey(null, '{{%events_ticket_types}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_ticket_types}}', 'primaryOwnerId', '{{%events_events}}', 'id', 'CASCADE', null);

        $this->addForeignKey(null, '{{%events_tickets}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_tickets}}', 'eventId', '{{%events_events}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_tickets}}', 'sessionId', '{{%events_sessions}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_tickets}}', 'typeId', '{{%events_ticket_types}}', 'id', 'CASCADE', null);


        //
        // Update Element Content
        //

        // Before proceeding, update the legacy Ticket Types and Ticket elements to have their content moved for Craft 4 > 5
        // We'll need to do this to fetch field values for the new Ticket Types/Ticket elements.
        $contentRefactorMigration = new BaseContentRefactorMigration();

        // Use reflection to access the protected method
        $reflectionClass = new ReflectionClass($contentRefactorMigration);
        $updateElements = $reflectionClass->getMethod('updateElements');
        $updateElements->setAccessible(true);

        $eventTypes = (new Query())
            ->from('{{%events_event_types}}')
            ->all();

        foreach ($eventTypes as $eventType) {
            $query = (new Query())->from('{{%events_events}}')->where(['typeId' => $eventType['id']]);

            if ($eventType['fieldLayoutId']) {
                $layout = Craft::$app->getFields()->getLayoutById($eventType['fieldLayoutId']);

                if ($layout) {
                    $updateElements->invoke($contentRefactorMigration, $query, $layout);
                }
            }
        }

        $ticketTypes = (new Query())
            ->from('{{%events_legacy_ticket_types}}')
            ->all();

        foreach ($ticketTypes as $ticketType) {
            if ($ticketType['fieldLayoutId']) {
                $layout = Craft::$app->getFields()->getLayoutById($ticketType['fieldLayoutId']);

                if ($layout) {
                    $ticketIds = (new Query())
                        ->select(['id'])
                        ->from('{{%events_legacy_tickets}}')
                        ->where(['typeId' => $ticketType['id']])
                        ->column();

                    if ($ticketIds) {
                        $updateElements->invoke($contentRefactorMigration, $ticketIds, $layout);
                    }

                    foreach ($ticketIds as $ticketId) {
                        $purchasedTicketIds = (new Query())
                            ->select(['id'])
                            ->from('{{%events_purchased_tickets}}')
                            ->where(['legacyTicketId' => $ticketId])
                            ->column();

                        if ($purchasedTicketIds) {
                            $updateElements->invoke($contentRefactorMigration, $purchasedTicketIds, $layout);
                        }
                    }

                    $updateElements->invoke($contentRefactorMigration, [$ticketType['id']], $layout);
                }
            }
        }

        //
        // Setup Field Layouts
        //

        // Session and Ticket Type field layouts are brand-new
        $eventTypes = (new Query())
            ->from('{{%events_event_types}}')
            ->all();

        foreach ($eventTypes as $eventType) {
            $eventFieldLayout = null;

            if ($eventType['fieldLayoutId']) {
                $eventFieldLayout = Craft::$app->getFields()->getLayoutById($eventType['fieldLayoutId']);
            }

            // Add the new fields, or create a new field layout
            $eventFieldLayout = $this->getEventFieldLayout($eventFieldLayout);

            // Sessions and Ticket Types will be brand-new
            $sessionFieldLayout = $this->getSessionFieldLayout();
            $ticketTypeFieldLayout = $this->getTicketTypeFieldLayout();

            Craft::$app->getFields()->saveLayout($eventFieldLayout);
            Craft::$app->getFields()->saveLayout($sessionFieldLayout);
            Craft::$app->getFields()->saveLayout($ticketTypeFieldLayout);

            // Setup defaults for titles/skus just in case
            $newAttributes = [
                'fieldLayoutId' => $eventFieldLayout->id,
                'sessionFieldLayoutId' => $sessionFieldLayout->id,
                'ticketTypeFieldLayoutId' => $ticketTypeFieldLayout->id,
                'sessionTitleFormat' => '{dateSummary}',
                'ticketTitleFormat' => '{type.title} - {session.title}',
                'purchasedTicketTitleFormat' => '{event.title} - {ticket.title}',
            ];

            $this->update('{{%events_event_types}}', $newAttributes, ['id' => $eventType['id']]);
        }

        //
        // Setup Sessions
        //

        // Sessions now store the event dates, and there will only ever be one for Events 2.x, so create them.
        $events = (new Query())
            ->from('{{%events_events}}')
            ->all();

        foreach ($events as $event) {
            // Find or create (in case we run this again)
            $session = Session::find()
                ->eventId($event['id'])
                ->startDate($event['startDate'])
                ->endDate($event['endDate'])
                ->one() ?? new Session();

            $session->setAttributes([
                'primaryOwnerId' => $event['id'],
                'startDate' => $event['startDate'],
                'endDate' => $event['endDate'],
                'allDay' => (bool)$event['allDay'],
            ], false);

            if (!Craft::$app->getElements()->saveElement($session)) {
                throw new Exception(Json::encode($session->getErrors()));
            }

            // Update all tickets with a reference to this session (it's the only one)
            $this->update('{{%events_tickets}}', ['sessionId' => $session->id], ['eventId' => $event['id']]);
            $this->update('{{%events_purchased_tickets}}', ['sessionId' => $session->id], ['eventId' => $event['id']]);
        }


        //
        // Setup Ticket Types
        //

        $legacyTickets = (new Query())
            ->from('{{%events_legacy_tickets}}')
            ->all();

        $updatedFieldLayouts = [];

        foreach ($legacyTickets as $legacyTicket) {
            $legacyTicketElement = LegacyTicket::find()
                ->id($legacyTicket['id'])
                ->one();

            $legacyTicketTypeElement = LegacyTicketType::find()
                ->id($legacyTicket['typeId'])
                ->one();

            $ticketType = new TicketType();

            $ticketType->setAttributes([
                'title' => $legacyTicketTypeElement->title ?? 'Ticket Type ' . rand(),
                'primaryOwnerId' => $legacyTicket['eventId'],
                'price' => $legacyTicket['price'],
                'capacity' => $legacyTicket['quantity'],
                'availableFrom' => $legacyTicket['availableFrom'],
                'availableTo' => $legacyTicket['availableTo'],
            ], false);

            $ticketType->setPrice($legacyTicket['price']);

            // Before adding content, ensure that the new ticket type has the same fields as the legacy ticket and type.
            $customFieldContent = [];

            $updateFieldLayout = function ($element) use ($ticketType, &$updatedFieldLayouts) {
                if ($element && ($legacyFieldLayout = $element->getFieldLayout())) {
                    $fieldLayout = $ticketType->getFieldLayout();

                    if (!in_array($fieldLayout->id, $updatedFieldLayouts)) {
                        $firstTab = $fieldLayout->getTabs()[0] ?? null;

                        if ($firstTab) {
                            $newElements = $legacyFieldLayout->getCustomFieldElements();

                            // Prevent duplicate fields from ticket and ticket type
                            foreach ($newElements as $newElementKey => $newElement) {
                                if ($fieldLayout->getFieldByHandle($newElement->field->handle)) {
                                    unset($newElements[$newElementKey]);
                                }
                            }

                            $firstTab->setElements(array_merge($firstTab->getElements(), $newElements));

                            Craft::$app->getFields()->saveLayout($fieldLayout);
                        }
                    }
                }
            };

            if ($legacyTicketElement) {
                $updateFieldLayout($legacyTicketElement);
                $customFieldContent[] = $legacyTicketElement->getFieldValues();
            }

            if ($legacyTicketTypeElement) {
                $updateFieldLayout($legacyTicketTypeElement);
                $customFieldContent[] = $legacyTicketTypeElement->getFieldValues();
            }

            $updatedFieldLayouts[] = $ticketType->getFieldLayout()->id;

            // Field content is combined from legacy ticket and ticket type. This handles duplicates as well.
            $customFieldContent = array_reduce($customFieldContent, function($carry, $item) {
                foreach ($item as $key => $value) {
                    if (!isset($carry[$key]) || $carry[$key] === null) {
                        $carry[$key] = $value;
                    }
                }
                return $carry;
            }, []);

            $ticketType->setFieldValues($customFieldContent);

            if (!Craft::$app->getElements()->saveElement($ticketType)) {
                throw new Exception(Json::encode($ticketType->getErrors()));
            }

            // Maintain a reference to the legacy ticket on the ticket type
            $this->update('{{%events_ticket_types}}', [
                'legacyTicketId' => $legacyTicket['id'],
                'legacyTicketTypeId' => $legacyTicket['typeId'],
            ], ['id' => $ticketType->id]);

            $this->update('{{%events_purchased_tickets}}', ['ticketTypeId' => $ticketType->id], ['legacyTicketId' => $legacyTicket['id']]);
        }


        //
        // Setup Tickets
        //

        foreach (Event::find()->all() as $event) {
            foreach ($event->getSessions() as $session) {
                foreach ($event->getTicketTypes() as $ticketType) {
                    $ticket = new Ticket([
                        'eventId' => $event->id,
                        'sessionId' => $session->id,
                        'typeId' => $ticketType->id,
                    ]);

                    if (!Craft::$app->getElements()->saveElement($ticket)) {
                        throw new Exception(Json::encode($ticket->getErrors()));
                    }

                    $legacyTicketId = (new Query())
                        ->select(['legacyTicketId'])
                        ->from('{{%events_ticket_types}}')
                        ->where(['id' => $ticketType->id])
                        ->scalar();

                    $legacyTicketTypeId = (new Query())
                        ->select(['typeId'])
                        ->from('{{%events_legacy_tickets}}')
                        ->where(['id' => $legacyTicketId])
                        ->scalar();

                    $this->update('{{%events_tickets}}', [
                        'legacyTicketId' => $legacyTicketId,
                        'legacyTicketTypeId' => $legacyTicketTypeId,
                    ], ['id' => $ticket->id]);

                    $this->update('{{%events_purchased_tickets}}', ['ticketId' => $ticket->id], ['legacyTicketId' => $legacyTicketId]);
                }
            }

            $this->update('{{%events_events}}', ['ticketsCache' => $event->getTicketCacheKey()], ['id' => $event->id]);
        }

        //
        // Commerce
        //

        // Update all line items to reflect the newly-created ticket, not the legacy one
        $tickets = (new Query())
            ->from('{{%events_tickets}}')
            ->all();

        foreach ($tickets as $ticket) {
            if (!$ticket['legacyTicketId']) {
                continue;
            }

            $this->update('{{%commerce_lineitems}}', ['purchasableId' => $ticket['id']], ['purchasableId' => $ticket['legacyTicketId']]);
        }


        // Perform cleanups at the end, once all is done
        $this->dropColumn('{{%events_events}}', 'allDay');
        $this->dropColumn('{{%events_events}}', 'startDate');
        $this->dropColumn('{{%events_events}}', 'endDate');

        $this->dropColumn('{{%events_event_types}}', 'hasTitleField');
        $this->dropColumn('{{%events_event_types}}', 'titleLabel');
        $this->dropColumn('{{%events_event_types}}', 'titleFormat');
        $this->dropColumn('{{%events_event_types}}', 'hasTickets');

        // Keep for legacy
        // $this->dropColumn('{{%events_purchased_tickets}}', 'ticketSku');

        // Clear out data caches, some element titles don't play well otherwise
        Craft::$app->getCache()->flush();

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240921_000000_events3 cannot be reverted.\n";

        return false;
    }

    private function getEventFieldLayout(?FieldLayout $fieldLayout = null): FieldLayout
    {
        if (!$fieldLayout) {
            $fieldLayout = new FieldLayout([
                'type' => Event::class,
            ]);

            // Populate the field layout
            $tab1 = new FieldLayoutTab(['name' => 'Content']);
            $tab1->setLayout($fieldLayout);

            $tab1->setElements([
                Craft::createObject([
                    'class' => LayoutFields\EventTitleField::class,
                ]),
                Craft::createObject([
                    'class' => LayoutFields\SessionsField::class,
                ]),
                Craft::createObject([
                    'class' => LayoutFields\TicketTypesField::class,
                ]),
                Craft::createObject([
                    'class' => LayoutFields\TicketsField::class,
                ]),
                Craft::createObject([
                    'class' => LayoutFields\PurchasedTicketsField::class,
                ]),
            ]);

            $fieldLayout->setTabs([$tab1]);
        } else {
            $firstTab = $fieldLayout->getTabs()[0];

            $firstTab->setElements(array_merge($firstTab->getElements(), [
                Craft::createObject([
                    'class' => LayoutFields\TicketsField::class,
                ]),
                Craft::createObject([
                    'class' => LayoutFields\PurchasedTicketsField::class,
                ]),
            ]));
        }

        return $fieldLayout;
    }

    private function getSessionFieldLayout(): FieldLayout
    {
        $fieldLayout = new FieldLayout([
            'type' => Session::class,
        ]);

        // Populate the field layout
        $tab1 = new FieldLayoutTab(['name' => 'Content']);
        $tab1->setLayout($fieldLayout);

        $tab1->setElements([
            Craft::createObject([
                'class' => LayoutFields\SessionStartDateTimeField::class,
            ]),
            Craft::createObject([
                'class' => LayoutFields\SessionEndDateTimeField::class,
            ]),
            Craft::createObject([
                'class' => LayoutFields\SessionAllDayField::class,
            ]),
            Craft::createObject([
                'class' => LayoutFields\SessionFrequencyField::class,
            ]),
            Craft::createObject([
                'class' => LayoutFields\PurchasedTicketsField::class,
            ]),
        ]);

        $fieldLayout->setTabs([$tab1]);

        return $fieldLayout;
    }

    private function getTicketTypeFieldLayout(): FieldLayout
    {
        $fieldLayout = new FieldLayout([
            'type' => TicketType::class,
        ]);

        // Populate the field layout
        $tab1 = new FieldLayoutTab(['name' => 'Content']);
        $tab1->setLayout($fieldLayout);

        $tab1->setElements([
            Craft::createObject([
                'class' => LayoutFields\TicketTypeTitleField::class,
            ]),
            Craft::createObject([
                'class' => LayoutFields\TicketTypeCapacityField::class,
            ]),
            Craft::createObject([
                'class' => LayoutFields\TicketTypePriceField::class,
            ]),
            Craft::createObject([
                'class' => LayoutFields\PurchasedTicketsField::class,
            ]),
        ]);

        $fieldLayout->setTabs([$tab1]);

        return $fieldLayout;
    }
}
