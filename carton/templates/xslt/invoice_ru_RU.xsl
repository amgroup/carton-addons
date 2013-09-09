<?xml version="1.1" encoding="UTF-8" ?>
<xsl:stylesheet
xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
xmlns:fo="http://www.w3.org/1999/XSL/Format"
xmlns:fox="http://xmlgraphics.apache.org/fop/extensions">
  
  <xsl:template match ="/">
    <fo:root>
      <fo:layout-master-set>
        <fo:simple-page-master master-name="job" page-width="21cm" page-height="29.7cm" margin="7mm 10mm 7mm 20mm">
          <fo:region-body margin-top="15mm" margin-bottom="15mm"/>
          <fo:region-before precedence="true" extent="15mm"/>
          <fo:region-after  precedence="true" extent="7mm"/>
        </fo:simple-page-master>
      </fo:layout-master-set>
      
      <fo:page-sequence master-reference="job">
        <fo:static-content flow-name="xsl-region-before" font-size="6pt" font-family="PT Sans Narrow">
          <fo:table width="100%" border-collapse="collapse" margin-bottom="5mm">
            <fo:table-column column-width="30%"/>
            <fo:table-column/>
            <fo:table-column column-width="30%"/>
            
            <fo:table-body>
              <fo:table-row border-bottom="0.25mm solid grey">
                <fo:table-cell display-align="top" text-align="start">
                  <fo:block margin-left="10mm" font-size="22pt">
                    <xsl:value-of select="/document/company/info/name" />
                  </fo:block>
                  <xsl:if test="string-length(/document/company/info/slogan)">
                    <fo:block margin-left="10mm" margin-top="-1mm" font-size="8pt">
                        <xsl:value-of select="/document/company/info/slogan" />
                    </fo:block>
                  </xsl:if>
                </fo:table-cell>
                
                <fo:table-cell display-align="top" text-align="center">
                  <fo:block font-size="8pt">
                    <xsl:if test="string-length(/document/company/info/phone)">Номер для справок: <xsl:value-of select="/document/company/info/phone" /><fo:block/></xsl:if>
                    <xsl:if test="string-length(/document/company/info/email)">Конткактный e-mail: <xsl:value-of select="/document/company/info/email" /><fo:block/></xsl:if>
                    <xsl:if test="string-length(/document/company/info/site)"><xsl:value-of select="/document/company/info/site" /><fo:block/></xsl:if>
                  </fo:block>
                </fo:table-cell>
                
                <fo:table-cell display-align="top">
                  <fo:block>
                    <xsl:choose>
                        <xsl:when test="string-length(/document/info/shipping/tracking)">
                            <fo:block border="0.5mm dashed red" fox:border-radius="3mm" text-align="center">
                                <fo:block font-weight="bold" font-family="Arial"><xsl:value-of select="/document/info/shipping/label" /></fo:block>
                                <xsl:call-template name="code128"><xsl:with-param name="string" select="/document/info/shipping/tracking" /><xsl:with-param name="label" select="'bottom'" /></xsl:call-template>
                            </fo:block>
                        </xsl:when>
                        <xsl:otherwise>
                            <fo:block text-align="end">
                                <fo:block font-weight="bold" font-family="Arial"><xsl:value-of select="/document/company/info/name" /></fo:block>
                                <xsl:call-template name="code128"><xsl:with-param name="string" select="/document/info/number" /><xsl:with-param name="label" select="'bottom'" /></xsl:call-template>
                            </fo:block>
                        </xsl:otherwise>
                    </xsl:choose>
                  </fo:block>
                </fo:table-cell>
                
              </fo:table-row>
              ></fo:table-body>
          </fo:table>
          
        </fo:static-content>
        
        <fo:static-content flow-name="xsl-region-after" font-size="6pt" font-family="Arial" color="grey" font-style="italic">
          <fo:block text-align="start" border-top="0.25mm solid grey">
            <xsl:if test="string-length(/document/notes/conditions)">* <xsl:value-of select="/document/notes/conditions" /></xsl:if>
          </fo:block>
        </fo:static-content>
        
        <fo:flow flow-name="xsl-region-body" font-family="Arial" font-size="8pt">
          <fo:block-container>
            <fo:block text-align="center" font-size="14pt" margin-bottom="5mm">
                <fo:inline font-weight="bold">Накладная</fo:inline> к заказу <fo:inline font-weight="bold"><xsl:value-of select="/document/info/number" /></fo:inline>
                <fo:inline text-align="center" font-size="9pt"> (от <xsl:value-of select="/document/info/date" />)</fo:inline>
            </fo:block>
            <fo:block>
              <fo:block text-align="start">
				Поставщик:
				<fo:inline font-weight="bold">
				  <xsl:value-of select="/document/company/info/address" />
				</fo:inline>
			  </fo:block>
              <fo:block text-align="start">
                Покупатель:&#032;
                <xsl:value-of select="/document/customer/name" />
                <xsl:if test="string-length(/document/customer/address)">,&#032;<xsl:value-of select="/document/customer/address" /></xsl:if>
              </fo:block>
              <fo:block text-align="start">
                <fo:inline font-weight="bold">
                  Получатель:&#032;
                  <xsl:value-of select="/document/recipient/name" />
                  <xsl:if test="string-length(/document/recipient/address)">,&#032;<xsl:value-of select="/document/recipient/address" /></xsl:if>
                </fo:inline>
              </fo:block>
              <fo:block margin-top="3mm" margin-bottom="5mm" padding-bottom="0.5mm" border-top="solid black 0.5mm" border-bottom="solid black 0.2mm" />
              <fo:table width="100%" border-collapse="collapse" margin-bottom="5mm">
                <fo:table-column column-width="3em"/>
                <fo:table-column />
                <fo:table-column column-width="5em"/>
                <fo:table-column column-width="8em" />
                
                <fo:table-header>
                  <fo:table-row text-align="center" display-align="center">
                    <fo:table-cell border="0.2mm solid black"><fo:block>№</fo:block></fo:table-cell>
                    <fo:table-cell border="0.2mm solid black"><fo:block>Название товара</fo:block></fo:table-cell>
                    <fo:table-cell border="0.2mm solid black"><fo:block>Кол-во,<fo:block/>(шт.)</fo:block></fo:table-cell>
                    <fo:table-cell border="0.2mm solid black"><fo:block>Сумма</fo:block></fo:table-cell>
                  </fo:table-row>
                </fo:table-header>
                
                <fo:table-body>
                  <xsl:for-each select="/document/items/item">
                    <fo:table-row display-align="center" margin="1mm">
                      <fo:table-cell border="0.2mm solid black" text-align="right"><fo:block><xsl:value-of select="position()"/></fo:block></fo:table-cell>
                      <fo:table-cell border="0.2mm solid black">
                        <fo:block  color="grey">
                          <!--xsl:call-template name="code128">
                            <xsl:with-param name="string" select="sku" />
                            <xsl:with-param name="height" select="'10mm'" />
                          </xsl:call-template-->
                          <xsl:value-of select="sku" />
                        </fo:block>
                        <fo:block>
                          <fo:inline font-weight="bold"><xsl:value-of select="name" /></fo:inline>
                          <fo:inline color="grey" font-size="7pt">
                            <xsl:for-each select="meta/dl/*">
                              <fo:inline>&#160;&#032;<xsl:value-of select="." /></fo:inline>
                            </xsl:for-each>
                          </fo:inline>
                        </fo:block>
                      </fo:table-cell>
                      <fo:table-cell border="0.2mm solid black" text-align="center"><fo:block><xsl:value-of select="qty" /></fo:block></fo:table-cell>
                      <fo:table-cell border="0.2mm solid black" text-align="right" padding-right="2mm">
                        <fo:block text-decoration="line-through" font-size="7pt">
                            <xsl:choose>
                                <xsl:when test="single_price/text() = price/text()"><xsl:attribute name="color"><xsl:value-of select="'white'"/></xsl:attribute></xsl:when>
                                <xsl:otherwise><xsl:attribute name="color"><xsl:value-of select="'grey'"/></xsl:attribute></xsl:otherwise>
                            </xsl:choose>
                            <xsl:value-of select="single_price" />
                        </fo:block>
                        <fo:block>
                            <xsl:value-of select="price" />
                        </fo:block>
                      </fo:table-cell>
                    </fo:table-row>
                  </xsl:for-each>

                </fo:table-body>
              </fo:table>
              
              <xsl:for-each select="/document/totals/total">
                <fo:block text-align="right" margin-right="2mm" font-size="9pt">
                  <xsl:value-of select="name" />: <fo:inline font-weight="bold"><xsl:value-of select="value" /></fo:inline>
                </fo:block>
              </xsl:for-each>
              
            </fo:block>
            
            <!-- Линия отреза -->
            <fo:block margin-top="10mm" margin-bottom="0mm" padding-bottom="0.5mm" border-top="dashed black 0.2mm" border-bottom="solid black 0.5mm" ></fo:block>
            <fo:block text-align="center" font-size="7pt" color="grey" font-style="italic">Линия отреза</fo:block>
            
            <fo:block text-align="center" font-size="14pt" margin-bottom="5mm">
                <fo:inline font-weight="bold">Курьерская заметка</fo:inline> к заказу <fo:inline font-weight="bold"><xsl:value-of select="/document/info/number" /></fo:inline>
                <fo:inline text-align="center" font-size="9pt"> (от <xsl:value-of select="/document/info/date" />)</fo:inline>
            </fo:block>

            <fo:block text-align="start" font-size="10pt">
              <fo:block>
                <fo:inline>
                  Получатель:&#032;
                </fo:inline>
                <fo:inline font-weight="bold" text-decoration="underline">
                  <xsl:if test="string-length(/document/recipient/phone)">т:&#160;<xsl:value-of select="/document/recipient/phone" />,&#032;</xsl:if>
                  <xsl:value-of select="/document/recipient/name" />
                  <xsl:if test="string-length(/document/recipient/email)">&#032;(<xsl:value-of select="/document/recipient/email" />)</xsl:if>
                  <xsl:if test="string-length(/document/recipient/address)">,&#032;<xsl:value-of select="/document/recipient/address" /></xsl:if>
                </fo:inline>
              </fo:block>
              
              <xsl:if test="string-length(/document/notes/customer)">
                  <fo:block>
                    <fo:inline>
                      Примечание покупателя:&#032;
                    </fo:inline>
                    <fo:inline font-weight="bold" text-decoration="underline">
                      <xsl:value-of select="/document/notes/customer" />
                    </fo:inline>
                  </fo:block>
              </xsl:if>
              
              <fo:block>
                <fo:inline>
                  Стоимость с учётом доставки:&#032;
                </fo:inline>
                <fo:inline font-weight="bold" text-decoration="underline">
                  <xsl:value-of select="/document/totals/total[last()]/value" />
                </fo:inline>
              </fo:block>
            </fo:block>
            
            <!-- Линия отреза -->
            <fo:block margin-top="5mm" margin-bottom="0mm" padding-bottom="0.5mm" border-top="dashed black 0.2mm" border-bottom="solid black 0.5mm" ></fo:block>
            <fo:block text-align="center" font-size="7pt" color="grey" font-style="italic">Линия отреза</fo:block>
             
            <fo:block text-align="end"><xsl:call-template name="code128"><xsl:with-param name="string" select="/document/info/number" /><xsl:with-param name="label" select="'bottom'" /></xsl:call-template></fo:block>
            
            <fo:block text-align="center" font-size="14pt" margin-bottom="5mm">
                <fo:inline font-weight="bold">Акт выполненых работ</fo:inline> к заказу <fo:inline font-weight="bold"><xsl:value-of select="/document/info/number" /></fo:inline>
                <fo:inline text-align="center" font-size="9pt"> (от <xsl:value-of select="/document/info/date" />)</fo:inline>
            </fo:block>
            
            <fo:block font-size="8pt" text-indent="10pt">Я, подписавшийся ниже, подтверждаю, что принял отмеченные мной, следующие по списку товары:</fo:block>
            
            <fo:table width="100%" border-collapse="collapse" margin-bottom="2mm" margin-top="2mm" keep-with-previous="always">
              <fo:table-column column-width="3em"/>
              <fo:table-column />
              <fo:table-column column-width="5em"/>
              <fo:table-column column-width="8em" />
              <fo:table-column column-width="10em" />
              
              <fo:table-header>
                <fo:table-row text-align="center" display-align="center">
                  <fo:table-cell border="0.2mm solid black"><fo:block>№</fo:block></fo:table-cell>
                  <fo:table-cell border="0.2mm solid black"><fo:block>Название товара</fo:block></fo:table-cell>
                  <fo:table-cell border="0.2mm solid black"><fo:block>Кол-во,<fo:block/>(шт.)</fo:block></fo:table-cell>
                  <fo:table-cell border="0.2mm solid black"><fo:block>Стоимость,<fo:block/>(руб.)</fo:block></fo:table-cell>
                  <fo:table-cell border="0.2mm solid black"><fo:block>Принял,<fo:block/>(подпись)</fo:block></fo:table-cell>
                </fo:table-row>
              </fo:table-header>
              
              <fo:table-body>
                <xsl:for-each select="/document/items/item">
                  <xsl:call-template name="for">
                    <xsl:with-param name="item" select="."/>
                    <xsl:with-param name="position" select="position()"/>
                    <xsl:with-param name="n" select="./qty"/>
                  </xsl:call-template>
                </xsl:for-each>
                
              </fo:table-body>
            </fo:table>
            
            <fo:block font-size="8pt" text-indent="10pt" keep-with-previous="always">
              Товары предоставлены мне в установленный срок и в надлежащем товарном виде, качеству принятых товаров на момент покупки притезий не имею&#160;&#032;
            </fo:block>
            
            <fo:block-container text-align="end" keep-with-previous="always">
              <fo:table border-collapse="collapse" margin-bottom="0mm" margin-top="0mm" text-align="end" keep-with-previous="always">
                <fo:table-column />
                <fo:table-column column-width="3cm" />
                <fo:table-column column-width="2mm" />
                <fo:table-column column-width="4cm" />
                
                <fo:table-body>
                  <fo:table-row display-align="center" keep-with-previous="always">
                    <fo:table-cell><fo:block></fo:block></fo:table-cell>
                    <fo:table-cell border-bottom="0.2mm solid black" padding="5mm"><fo:block></fo:block></fo:table-cell>
                    <fo:table-cell><fo:block></fo:block></fo:table-cell>
                    <fo:table-cell border-bottom="0.2mm solid black" padding="5mm"><fo:block></fo:block></fo:table-cell>
                  </fo:table-row>
                  <fo:table-row display-align="center" keep-with-previous="always">
                    <fo:table-cell><fo:block></fo:block></fo:table-cell>
                    <fo:table-cell font-size="6pt" text-align="center" font-style="italic"><fo:block>Подпись</fo:block></fo:table-cell>
                    <fo:table-cell text-align="center"><fo:block></fo:block></fo:table-cell>
                    <fo:table-cell font-size="6pt" text-align="center" font-style="italic"><fo:block>Расшифровка подписи</fo:block></fo:table-cell>
                  </fo:table-row>
                </fo:table-body>
              </fo:table>
            </fo:block-container>
            
          </fo:block-container>
        </fo:flow>
      </fo:page-sequence>
      
    </fo:root>
  </xsl:template>
  
  <xsl:template name="code128">
    <xsl:param name="string" />
    <xsl:param name="height" select="'10mm'" />
    <xsl:param name="label" select="'none'"/>
    
    <fo:inline>
      <fo:instream-foreign-object>
        <barcode:barcode xmlns:barcode="http://barcode4j.krysalis.org/ns" orientation="0">
          <xsl:attribute name="message"><xsl:value-of select="$string"/></xsl:attribute>
          <barcode:code128>
            <barcode:height><xsl:value-of select="$height"/></barcode:height>
            <barcode:module-width>0.25mm</barcode:module-width>
            <!--barcode:codesets>ABC</barcode:codesets-->
            <barcode:quiet-zone enabled="false">10mw</barcode:quiet-zone>
            <barcode:human-readable>
              <barcode:placement><xsl:value-of select="$label"/></barcode:placement>
              <barcode:font-name>Arial</barcode:font-name><barcode:font-size>3mm</barcode:font-size>
            </barcode:human-readable>
          </barcode:code128>
        </barcode:barcode>
      </fo:instream-foreign-object>
    </fo:inline>
  </xsl:template>
  
  <xsl:template name="qrcode">
    <xsl:param name="string" />
    
    <fo:instream-foreign-object>
      <qr:qr-code xmlns:qr="http://hobbut.ru/fop/qr-code/"
      width="5cm"
      message="Hello, Zxing!"
      correction="l">
      </qr:qr-code>
    </fo:instream-foreign-object>
  </xsl:template>
  
  
  <xsl:template name="for">
    <xsl:param name="item"/>
    <xsl:param name="position" select="'1'"/>
    <xsl:param name="i" select="0"/>
    <xsl:param name="n"/>
    <xsl:param name="last" select="0"/>
    <xsl:param name="current" select="1"/>
    <xsl:if test="$i &lt; $n">
      
      <fo:table-row display-align="center" margin="1mm">
        <fo:table-cell border="0.2mm solid black" text-align="right">
          <fo:block>
            <fo:inline font-weight="bold"><xsl:value-of select="$position"/></fo:inline><xsl:if test="1 &lt; $item/qty"><fo:inline color="grey">.<xsl:value-of select="($i+1)"/></fo:inline></xsl:if>
          </fo:block>
        </fo:table-cell>
        <fo:table-cell border="0.2mm solid black">
          <fo:block color="grey" margin-left="3mm">
			<fo:inline padding-right="3mm">
              <xsl:call-template name="code128">
                <xsl:with-param name="string" select="$item/sku" />
                <xsl:with-param name="height" select="'7mm'" />
              </xsl:call-template>
                          </fo:inline>
                          (артикул: <xsl:value-of select="$item/sku" />)
                        </fo:block>
                        <fo:block margin-left="3mm" keep-with-previous="always">
                          <fo:inline font-weight="bold"><xsl:value-of select="$item/name" /></fo:inline>
                          <fo:inline color="grey" font-size="7pt">
                            <xsl:for-each select="$item/meta/dl/*">
                              <fo:inline>&#160;&#032;<xsl:value-of select="." /></fo:inline>
                            </xsl:for-each>
                          </fo:inline>
                        </fo:block>
                      </fo:table-cell>
                      <fo:table-cell border="0.2mm solid black" text-align="center"><fo:block><xsl:value-of select="'1'" /></fo:block></fo:table-cell>
                      <fo:table-cell border="0.2mm solid black" text-align="right" padding-right="2mm">
                        <xsl:if test="$item/single_price/text() != $item/price/text()">
                            <fo:block text-decoration="line-through" font-size="7pt" color="grey"><xsl:value-of select="$item/single_price" /></fo:block>
                        </xsl:if>
                        <fo:block><xsl:value-of select="$item/price" /></fo:block>
                      </fo:table-cell>
                      <fo:table-cell border="0.2mm solid black" text-align="right" padding-right="2mm"><fo:block></fo:block></fo:table-cell>
    </fo:table-row>
    
    <xsl:value-of select="$current"/>
    <xsl:call-template name="for">
      <xsl:with-param name="item" select="$item" />
      <xsl:with-param name="i" select="$i + 1"/>
      <xsl:with-param name="n" select="$n"/>
      <xsl:with-param name="last" select="$current"/>
      <xsl:with-param name="current" select="$last + $current"/>
    </xsl:call-template>
  </xsl:if>
  </xsl:template>
  
</xsl:stylesheet>
