<?php
namespace Craft;

class Events_TicketTypeElementType extends BaseElementType
{
	public function getName()
	{
		return Craft::t('Ticket Type');
	}

	public function hasTitles()
	{
		return true;
	}

	public function hasContent()
	{
		return true;
	}

    public function isLocalized()
    {
        return true;
    }

	public function getSources($context = null)
	{
		$sources = array(
			'*' => array(
				'label' => Craft::t('All ticket types'),
			)
		);

		return $sources;
	}

	public function defineTableAttributes($source = null)
	{
		return array(
			'title' => Craft::t('Title'),
		);
	}

    public function defineSortableAttributes()
    {
        $attributes = [
            'id' => Craft::t('ID'),
            'title' => Craft::t('Title'),
        ];

        // Allow plugins to modify the attributes
//        craft()->plugins->call('events_modifyEventSortableAttributes', [&$attributes]);

        return $attributes;
    }

	public function defineCriteriaAttributes()
	{
		return array(
			'id'		=> array(AttributeType::Number),
            //'title' 	=> array(AttributeType::String),
            //'name'      => array( AttributeType::Name ),
            'handle'    => array( AttributeType::Handle ),
//            'hasUrls'   => array( AttributeType::Bool ),
//            'urlFormat' => array( AttributeType::String ),
//            'skuFormat' => array( AttributeType::String ),
//            'template'  => array( AttributeType::Template ),
		);
	}

	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
        switch ($attribute) {
            default: {
				return parent::getTableAttributeHtml($element, $attribute);
            }
        }
	}

	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
		->addSelect('tickets.*')
		->join('events_tickettypes tickets', 'tickets.id = elements.id');

//        if ($criteria->id) {
//            $query->andWhere(DbHelper::parseParam('tickets.id', $criteria->id, $query->params));
//        }

//        if ($criteria->title) {
//            $query->andWhere(DbHelper::parseParam('tickets.title', $criteria->title, $query->params));
//        }

//        if ($criteria->handle) {
//            $query->andWhere(DbHelper::parseParam('tickets.handle', $criteria->handle, $query->params));
//        }
	}

	public function getEditorHtml(BaseElementModel $element)
	{
		$html = craft()->templates->render('events/tickettypes/_editor', array(
			'element' => $element
		));

		$html .= parent::getEditorHtml($element);

		return $html;
	}

	public function populateElementModel($row)
	{
		return Events_TicketTypeModel::populateModel($row);
	}
}