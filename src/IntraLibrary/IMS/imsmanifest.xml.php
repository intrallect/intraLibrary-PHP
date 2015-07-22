<?php
// @codingStandardsIgnoreFile
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<manifest xmlns="http://www.imsglobal.org/xsd/imscp_v1p1"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.imsglobal.org/xsd/imscp_v1p1 http://www.imsglobal.org/xsd/imscp_v1p1p3.xsd"
    identifier="MANIFEST1">
    <metadata>
        <schema>IMS Content</schema>
        <schemaversion>1.2.2</schemaversion>
        <lom:lom xmlns:lom="http://ltsc.ieee.org/xsd/LOM"
            xmlns:ns0="http://www.intrallect.com/metadata_model/star_rating"
            xmlns:ns1="http://www.intrallect.com/metadata_model/annotation"
            xsi:schemaLocation="http://ltsc.ieee.org/xsd/LOM http://ltsc.ieee.org/xsd/lomv1.0/lomLoose.xsd">
            <lom:general>
<?php if (isset($CatalogueName, $CatalogueId)) : ?>
                <lom:identifier>
                    <lom:catalog><?php echo $CatalogueName; ?></lom:catalog>
                    <lom:entry><?php echo $CatalogueId; ?></lom:entry>
                </lom:identifier>
<?php endif; ?>
                <lom:title>
                    <lom:string language="en"><?php echo $Title ?></lom:string>
                </lom:title>
<?php foreach ($Descriptions as $Description) : ?>
                <lom:description>
                    <lom:string language="en"><?php echo $Description ?></lom:string>
                </lom:description>
<?php endforeach; ?>
                <lom:language>en</lom:language>
<?php if (isset($Keywords)) : ?>
<?php foreach ($Keywords as $keyword) : ?>
                <lom:keyword>
                    <lom:string language="en"><?php echo $keyword; ?></lom:string>
                </lom:keyword>
<?php endforeach; ?>
<?php endif; ?>
            </lom:general>
<?php if (isset($FullName) || isset($Organisation) || isset($Email)) : ?>
            <lom:lifeCycle>
                <lom:contribute>
                    <lom:role>
                        <lom:source>LOMv1.0</lom:source>
                        <lom:value>content provider</lom:value>
                    </lom:role>
                    <lom:entity>BEGIN:vcard&#xD;
                        <?php if (isset($FullName)) : ?>FN:<?php echo $FullName; ?>&#xD;<?php endif; ?>
                        <?php if (isset($Organisation)) : ?>ORG:<?php echo $Organisation; ?>&#xD;<?php endif; ?>
                        <?php if (isset($Email)) : ?>EMAIL:<?php echo $Email; ?>&#xD;<?php endif; ?>
                        END:vcard
                    </lom:entity>
                    <lom:date>
                        <lom:dateTime><?php echo $DateTime; ?></lom:dateTime>
                    </lom:date>
                </lom:contribute>
            </lom:lifeCycle>
            <lom:metaMetadata>
                <lom:contribute>
                    <lom:role>
                        <lom:source>LOMv1.0</lom:source>
                        <lom:value>creator</lom:value>
                    </lom:role>
                    <lom:entity>BEGIN:vcard&#xD;
                        <?php if (isset($FullName)) : ?>FN:<?php echo $FullName; ?>&#xD;<?php endif; ?>
                        <?php if (isset($Organisation)) : ?>ORG:<?php echo $Organisation; ?>&#xD;<?php endif; ?>
                        <?php if (isset($Email)) : ?>EMAIL:<?php echo $Email; ?>&#xD;<?php endif; ?>
                        END:vcard
                    </lom:entity>
                    <lom:date>
                        <lom:dateTime><?php echo $DateTime; ?></lom:dateTime>
                    </lom:date>
                </lom:contribute>
                <lom:metadataSchema>IEEE LOM 1.0</lom:metadataSchema>
                <lom:language>en</lom:language>
            </lom:metaMetadata>
<?php endif; ?>
            <lom:technical>
<?php if (isset($TechnicalFormat)) : ?>
                <lom:format><?php echo $TechnicalFormat; ?></lom:format>
<?php endif; ?>
<?php if (isset($TechnicalSize)) : ?>
                <lom:size><?php echo $TechnicalSize; ?></lom:size>
<?php endif; ?>
<?php if (isset($TechnicalLocation)) : ?>
                <lom:location><?php echo $TechnicalLocation; ?></lom:location>
<?php endif; ?>
            </lom:technical>
<?php if (isset($EducationalDescription)) : ?>
            <lom:educational>
                <!-- Educational Section -->
                <lom:description>
                    <lom:string language="en"><?php echo $EducationalDescription; ?></lom:string>
                </lom:description>
            </lom:educational>
<?php endif; ?>
<?php if (isset($Copyright)) : ?>
            <lom:rights>
                <lom:copyrightAndOtherRestrictions>
                    <lom:source>LOMv1.0</lom:source>
                    <lom:value>yes</lom:value>
                </lom:copyrightAndOtherRestrictions>
                <lom:description>
                    <lom:string language="en"><?php echo $Copyright; ?></lom:string>
                </lom:description>
            </lom:rights>
<?php endif; ?>
<?php if (isset($ResourceType, $ResourceTypeSource)) : ?>
            <lom:educational>
                <lom:learningResourceType>
                    <lom:source><?php echo $ResourceTypeSource; ?></lom:source>
                    <lom:value><?php echo $ResourceType; ?></lom:value>
                </lom:learningResourceType>
            </lom:educational>
<?php endif; ?>
            <lom:classification>
                <lom:purpose>
                    <lom:source>LOMv1.0</lom:source>
                    <lom:value>discipline</lom:value>
                </lom:purpose>
<?php foreach ($TaxonPaths as $taxonPath) : ?>
                <lom:taxonPath>
                    <lom:source>
                        <lom:string language="en"><?php echo $taxonPath['source']; ?></lom:string>
                    </lom:source>
<?php foreach ($taxonPath['taxons'] as $taxon) : ?>
                    <lom:taxon>
                        <lom:id><?php echo $taxon['refId'] ?></lom:id>
                        <lom:entry>
                            <lom:string language="en"><?php echo $taxon['name'] ?></lom:string>
                        </lom:entry>
                    </lom:taxon>
<?php endforeach; ?>
                </lom:taxonPath>
<?php endforeach; ?>
            </lom:classification>
        </lom:lom>
    </metadata>
    <organizations default="ORG_1">
        <organization identifier="ORG_1">
            <title>default organization</title>
            <item identifierref="<?php echo $MainIdentifier ?>" identifier="ITEM_1">
                <title><?php echo isset($FileName) ? $FileName : $Title; ?></title>
            </item>
        </organization>
    </organizations>
    <resources>
<?php if (isset($FileName)) : ?>
        <resource href="<?php echo $FileName ?>" identifier="<?php echo $MainIdentifier ?>" type="webcontent">
            <file href="<?php echo $FileName ?>" />
        </resource>
<?php endif; ?>
    </resources>
</manifest>
