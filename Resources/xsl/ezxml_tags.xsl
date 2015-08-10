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
            <xsl:attribute name="href">
                <xsl:value-of select="@url" />
            </xsl:attribute>

            <xsl:if test="@target != ''">
                <xsl:attribute name="target">
                    <xsl:value-of select="@target" />
                </xsl:attribute>
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
            <xsl:if test="@custom:video_service='youtube'">
                <xsl:attribute name="class">
                    <xsl:text>ct-video youtube</xsl:text>
                </xsl:attribute>

                <div>
                    <xsl:attribute name="style"><xsl:text>display:none;</xsl:text></xsl:attribute>
                    <xsl:attribute name="class">
                        <xsl:text>video-config video-external-</xsl:text>
                        <xsl:value-of select="@custom:video_code" />
                    </xsl:attribute>
                    <xsl:attribute name="data-video_player_id">
                        <xsl:text>video-external-</xsl:text>
                        <xsl:value-of select="@custom:video_code" />
                    </xsl:attribute>
                    <xsl:attribute name="autostart"><xsl:text>false</xsl:text></xsl:attribute>
                    <xsl:attribute name="width"><xsl:text>640</xsl:text></xsl:attribute>
                    <xsl:attribute name="height"><xsl:text>360</xsl:text></xsl:attribute>
                    <xsl:attribute name="data-videotype"><xsl:text>youtube</xsl:text></xsl:attribute>
                    <xsl:attribute name="data-file">
                        <xsl:text>https://www.youtube.com/watch?v=</xsl:text>
                        <xsl:value-of select="@custom:video_code" />
                    </xsl:attribute>
                </div>

                <div>
                    <xsl:attribute name="class"><xsl:text>video-container</xsl:text></xsl:attribute>
                    <xsl:attribute name="id">
                        <xsl:text>video-external-</xsl:text>
                        <xsl:value-of select="@custom:video_code" />
                    </xsl:attribute>
                </div>
            </xsl:if>

            <xsl:if test="@custom:video_service='vimeo'">
                <xsl:attribute name="class">
                    <xsl:text>ct-video vimeo</xsl:text>
                </xsl:attribute>

                <iframe>
                    <xsl:attribute name="width"><xsl:text>640</xsl:text></xsl:attribute>
                    <xsl:attribute name="height"><xsl:text>360</xsl:text></xsl:attribute>
                    <xsl:attribute name="src">
                        <xsl:text>//player.vimeo.com/video/</xsl:text>
                        <xsl:value-of select="@custom:video_code" />
                    </xsl:attribute>
                </iframe>
            </xsl:if>
        </div>
    </xsl:template>

</xsl:stylesheet>
