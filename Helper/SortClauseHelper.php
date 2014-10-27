<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Location;

class SortClauseHelper
{
    /**
     * Returns query sort order by location sort order
     *
     * @param int $sortOrder
     *
     * @return string
     */
    public function getQuerySortOrder( $sortOrder )
    {
        if ( $sortOrder === Location::SORT_ORDER_DESC )
        {
            return Query::SORT_DESC;
        }

        return Query::SORT_ASC;
    }

    /**
     * Instantiates a correct sort clause object based on provided location sort field and sort order
     *
     * @param string $sortField
     * @param int $sortOrder
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Query\SortClause
     */
    public function getSortClauseBySortField( $sortField, $sortOrder = Location::SORT_ORDER_ASC )
    {
        switch ( $sortField )
        {
            case 'path':
            case 'path_string':
            case Location::SORT_FIELD_PATH:
                return new SortClause\Location\Path( $this->getQuerySortOrder( $sortOrder ) );

            case 'published':
            case Location::SORT_FIELD_PUBLISHED:
                return new SortClause\DatePublished( $this->getQuerySortOrder( $sortOrder ) );

            case 'modified':
            case Location::SORT_FIELD_MODIFIED:
                return new SortClause\DateModified( $this->getQuerySortOrder( $sortOrder ) );

            case 'section':
            case Location::SORT_FIELD_SECTION:
                return new SortClause\SectionIdentifier( $this->getQuerySortOrder( $sortOrder ) );

            case 'depth':
            case Location::SORT_FIELD_DEPTH:
                return new SortClause\Location\Depth( $this->getQuerySortOrder( $sortOrder ) );

            //@todo: sort clause not yet implemented
            // case 'class_identifier'
            // case Location::SORT_FIELD_CLASS_IDENTIFIER:

            //@todo: sort clause not yet implemented
            // case 'class_name'
            // case Location::SORT_FIELD_CLASS_NAME:

            case 'priority':
            case Location::SORT_FIELD_PRIORITY:
                return new SortClause\Location\Priority( $this->getQuerySortOrder( $sortOrder ) );

            case 'name':
            case Location::SORT_FIELD_NAME:
                return new SortClause\ContentName( $this->getQuerySortOrder( $sortOrder ) );

            //@todo: sort clause not yet implemented
            // case Location::SORT_FIELD_MODIFIED_SUBNODE:

            case Location::SORT_FIELD_NODE_ID:
                return new SortClause\Location\Id( $this->getQuerySortOrder( $sortOrder ) );

            case Location::SORT_FIELD_CONTENTOBJECT_ID:
                return new SortClause\ContentId( $this->getQuerySortOrder( $sortOrder ) );

            default:
                return new SortClause\Location\Path( $this->getQuerySortOrder( $sortOrder ) );
        }
    }
}
