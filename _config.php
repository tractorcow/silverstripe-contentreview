<?php

Object::add_extension('SiteTree', 'SiteTreeContentReview');
Object::add_extension('CMSMain', 'LeftAndMainContentReview');


if(class_exists('Subsite') && class_exists('SubsiteReportWrapper')){
	SS_Report::register('ReportAdmin', 'SubsiteReportWrapper("PagesDueForReviewReport")',20);
} else {
	SS_Report::register('ReportAdmin', 'PagesDueForReviewReport',20);
}
