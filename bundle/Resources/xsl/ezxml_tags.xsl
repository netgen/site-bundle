<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
        version="1.0"
        xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
        xmlns:xhtml="http://ez.no/namespaces/ezpublish3/xhtml/"
        xmlns:custom="http://ez.no/namespaces/ezpublish3/custom/"
        xmlns:image="http://ez.no/namespaces/ezpublish3/image/"
        exclude-result-prefixes="xhtml custom image">

    <xsl:output method="html" indent="yes" encoding="UTF-8" />

    <xsl:template match="link">
        <a>
            <xsl:choose>
                <xsl:when test="@custom:link_suffix != ''">
                    <xsl:attribute name="href">
                       <xsl:value-of select="concat(@url, @custom:link_suffix)" />
                    </xsl:attribute>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="href">
                        <xsl:value-of select="@url" />
                    </xsl:attribute>
                </xsl:otherwise>
            </xsl:choose>

            <xsl:if test="@target != ''">
                <xsl:attribute name="target">
                    <xsl:value-of select="@target" />
                </xsl:attribute>

                <xsl:attribute name="rel"><xsl:text>nofollow noopener noreferrer</xsl:text></xsl:attribute>
            </xsl:if>

            <xsl:if test="@xhtml:title != ''">
                <xsl:attribute name="title">
                    <xsl:value-of select="@xhtml:title" />
                </xsl:attribute>
            </xsl:if>

            <xsl:if test="@xhtml:id != ''">
                <xsl:attribute name="id">
                    <xsl:value-of select="@xhtml:id" />
                </xsl:attribute>
            </xsl:if>

            <xsl:copy-of select="@class" />

            <xsl:apply-templates/>
        </a>
    </xsl:template>

    <xsl:template match="custom[@name='separator']">
        <div>
            <xsl:attribute name="class">separator</xsl:attribute>

            <div>
                <xsl:attribute name="class">separator-design</xsl:attribute>
                <xsl:apply-templates />
            </div>
        </div>
    </xsl:template>

    <xsl:template match="custom[@name='video']">
        <div>
            <xsl:attribute name="class">
                <xsl:text>video-</xsl:text>
                <xsl:value-of select="@custom:video_service" />
                <xsl:text> embed-responsive embed-responsive-16by9</xsl:text>
            </xsl:attribute>

            <iframe>
                <xsl:attribute name="class"><xsl:text>embed-responsive-item</xsl:text></xsl:attribute>
                <xsl:attribute name="frameborder"><xsl:text>0</xsl:text></xsl:attribute>
                <xsl:attribute name="allowfullscreen"><xsl:text>true</xsl:text></xsl:attribute>
                <xsl:attribute name="width"><xsl:text>770</xsl:text></xsl:attribute>
                <xsl:attribute name="height"><xsl:text>433</xsl:text></xsl:attribute>
                <xsl:attribute name="src">
                    <xsl:if test="@custom:video_service='youtube'">
                        <xsl:text>https://www.youtube.com/embed/</xsl:text>
                    </xsl:if>
                    <xsl:if test="@custom:video_service='vimeo'">
                        <xsl:text>https://player.vimeo.com/video/</xsl:text>
                    </xsl:if>
                    <xsl:if test="@custom:video_service='dailymotion'">
                        <xsl:text>https://www.dailymotion.com/embed/video/</xsl:text>
                    </xsl:if>

                    <xsl:value-of select="@custom:video_code" />
                </xsl:attribute>
            </iframe>
        </div>
    </xsl:template>

    <xsl:template match="custom[@name='quote']">
        <div>
            <xsl:attribute name="class">
                <xsl:choose>
                    <xsl:when test="@custom:align != ''">
                        <xsl:value-of select="concat( 'object-', @custom:align )" />
                    </xsl:when>
                    <xsl:otherwise>object-full</xsl:otherwise>
                </xsl:choose>
            </xsl:attribute>

            <blockquote>
                <div>
                    <xsl:attribute name="class">blockquote-body</xsl:attribute>
                    <xsl:apply-templates />
                </div>

                <xsl:if test="@custom:author != ''">
                    <small>
                        <xsl:value-of select="@custom:author" />
                    </small>
                </xsl:if>
            </blockquote>
        </div>
    </xsl:template>

    <xsl:template match="custom[@name='factbox']">
        <div>
            <xsl:attribute name="class">
                <xsl:choose>
                    <xsl:when test="@custom:align != ''">
                        <xsl:value-of select="concat( 'factbox object-', @custom:align )" />
                    </xsl:when>
                    <xsl:otherwise>factbox object-full</xsl:otherwise>
                </xsl:choose>
            </xsl:attribute>

            <xsl:if test="@custom:title != ''">
                <div>
                    <xsl:attribute name="class">factbox-header</xsl:attribute>
                    <h2>
                        <xsl:value-of select="@custom:title" />
                    </h2>
                </div>
            </xsl:if>

            <div>
                <xsl:attribute name="class">factbox-content</xsl:attribute>
                <xsl:apply-templates />
            </div>
        </div>
    </xsl:template>

    <xsl:template match="table">
        <xsl:choose>
            <xsl:when test="@custom:responsive != ''">
            <div class="table-responsive">
                <xsl:element name="table" use-attribute-sets="ngsite-table">
                    <xsl:if test="@custom:caption != ''">
                        <caption>
                            <xsl:value-of select="@custom:caption" />
                        </caption>
                    </xsl:if>

                    <xsl:apply-templates/>
                </xsl:element>
            </div>
            </xsl:when>
            <xsl:otherwise>
                <xsl:element name="table" use-attribute-sets="ngsite-table">
                    <xsl:if test="@custom:caption != ''">
                        <caption>
                            <xsl:value-of select="@custom:caption" />
                        </caption>
                    </xsl:if>

                    <xsl:apply-templates/>
                </xsl:element>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:attribute-set name="ngsite-table">
        <xsl:attribute name="class">
            <xsl:choose>
                <xsl:when test="@class != ''">
                    <xsl:value-of select="concat( 'table ', @class )" />
                    <xsl:if test="@align != ''">
                        <xsl:value-of select="concat( ' object-', @align )" />
                    </xsl:if>
                </xsl:when>
                <xsl:otherwise>table</xsl:otherwise>
            </xsl:choose>
        </xsl:attribute>

        <xsl:attribute name="style">
            <xsl:if test="@width != '100%'">
                <xsl:value-of select="concat( 'width:', @width, ';' )" />
            </xsl:if>
            <xsl:if test="@border != '0'">
                <xsl:value-of select="concat( 'border:', @border, 'px solid;' )" />
            </xsl:if>
        </xsl:attribute>
    </xsl:attribute-set>

    <xsl:template match="td | th">
        <xsl:copy>
            <xsl:choose>
                <xsl:when test="@valign">
                    <xsl:attribute name="style">vertical-align: <xsl:value-of select="@valign" />;</xsl:attribute>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="style">vertical-align: top;</xsl:attribute>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:if test="@xhtml:colspan">
                <xsl:attribute name="colspan">
                    <xsl:value-of select="@xhtml:colspan" />
                </xsl:attribute>
            </xsl:if>
            <xsl:if test="@xhtml:rowspan">
                <xsl:attribute name="rowspan">
                    <xsl:value-of select="@xhtml:rowspan" />
                </xsl:attribute>
            </xsl:if>
            <xsl:if test="@xhtml:width">
                <xsl:attribute name="width">
                    <xsl:value-of select="@xhtml:width" />
                </xsl:attribute>
            </xsl:if>
            <xsl:copy-of select="@class"/>
            <xsl:copy-of select="@align"/>
            <xsl:apply-templates/>
        </xsl:copy>
    </xsl:template>

    <xsl:template match="custom[@name='google_maps']">
        <xsl:variable name="latitude"><xsl:value-of select="@custom:latitude"></xsl:value-of></xsl:variable>
        <xsl:variable name="longitude"><xsl:value-of select="@custom:longitude"></xsl:value-of></xsl:variable>
        <xsl:variable name="container_id">
            <xsl:choose>
                <xsl:when test="@custom:container_id != ''" >
                    <xsl:value-of select="@custom:container_id"></xsl:value-of>
                </xsl:when>
                <xsl:otherwise>0</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="zoom">
            <xsl:choose>
                <xsl:when test="@custom:zoom != ''">
                    <xsl:value-of select="@custom:zoom"></xsl:value-of>
                </xsl:when>
                <xsl:otherwise>13</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="map_type">
            <xsl:choose>
                <xsl:when test="@custom:map_type != ''">
                    <xsl:value-of select="@custom:map_type"></xsl:value-of>
                </xsl:when>
                <xsl:otherwise>ROADMAP</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="map_height">
            <xsl:choose>
                <xsl:when test="@custom:map_height != ''">
                    <xsl:value-of select="@custom:map_height"></xsl:value-of>
                </xsl:when>
                <xsl:otherwise>560</xsl:otherwise>
            </xsl:choose>
        </xsl:variable>

        <script type="text/javascript">
            google.maps.event.addDomListener(window, 'load', function(){
                initializeGoogleMaps({
                    containerId: "<xsl:value-of select="$container_id"/>",
                    latitude: <xsl:value-of select="$latitude"/>,
                    longitude: <xsl:value-of select="$longitude"/>,
                    zoom: <xsl:value-of select="$zoom"/>,
                    mapType: "<xsl:value-of select="$map_type"/>"
                });
            });
        </script>

        <div id="map-canvas-{$container_id}" class="google-maps" style="height:{$map_height}px; width:100%;"></div>
    </xsl:template>
</xsl:stylesheet>
