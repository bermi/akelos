<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="/">
        <html>
	        <head>
		        <title>Xinc Build Report </title>
		        <style>
			body {
				font-family : Helvetica;
				font-size : 16px;
			}
		
			h3 {

			}

			div.target {


			}

			div.stacktrace {
				color:red;
			}
		        </style>
	        </head>
	        <body>
	            <h1>Build Report <xsl:value-of select="build/@time"/> :</h1>
	            <xsl:for-each select="build/target">
	                <div class="target">
		                <h3><xsl:value-of select="@name"/>: <xsl:value-of select="@time"/></h3>
		                <div class="task">
		                    <xsl:for-each select="task">
			                    <xsl:value-of select="@name"/> : 
			                    <xsl:value-of select="@time"/>
		                    </xsl:for-each>	
		                </div>
	                </div>
	            </xsl:for-each>
	            <xsl:for-each select="build/stacktrace">
	                <div class="stacktrace">
		                <xsl:value-of select="."/>
	                </div>
	            </xsl:for-each>
	            <xsl:for-each select="build/message">
	                <p>
	                    <xsl:choose>
	                        <xsl:when test="@priority ='debug'">
		                        <b style="color:green"><xsl:value-of select="@priority"/></b>  
	                        </xsl:when>
	                        <xsl:when test="@priority ='warn'">
		                        <b style="color:red"><xsl:value-of select="@priority"/></b>  
	                        </xsl:when>
	                        <xsl:when test="@priority ='error'">
		                        <b style="color:red"><xsl:value-of select="@priority"/></b>  
	                        </xsl:when>
	                        <xsl:otherwise>
		                        <b style="color:blue"><xsl:value-of select="@priority"/></b>  
	                        </xsl:otherwise>
	                    </xsl:choose>
	                    <xsl:value-of select="."/>	
	                </p>
	            </xsl:for-each>
	        </body>
        </html>
    </xsl:template>
</xsl:stylesheet>