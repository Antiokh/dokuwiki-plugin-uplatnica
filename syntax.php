<?php

/**
 * DokuWiki Payment Receipt Plugin
 * Copyright (C) 2024 Your Name <your.email@example.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class syntax_plugin_uplatnica extends DokuWiki_Syntax_Plugin
{
    public function getType()
    {
        return 'substition';
    }

    public function getSort()
    {
        return 32;
    }

    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('<uplatnica[^>]*/?>', $mode, 'plugin_uplatnica');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $data = new stdClass();
        
        // Parse <uplatnica /> tag
        try {
            $uplatnica = new SimpleXMLElement($match);
        } catch (Exception $e) {
            $data->error = 'error parsing uplatnica tag';
            return $data;
        }

        // Parse attributes
        $attributes = new stdClass();
        $attributes->payer = $uplatnica->attributes()['payer'] ? strval($uplatnica->attributes()['payer']) : 'Imya Familija, address';
        $attributes->address = strval($uplatnica->attributes()['address']);
        $attributes->subject = strval($uplatnica->attributes()['subject']);
        $attributes->recipient = strval($uplatnica->attributes()['recipient']);
        $attributes->code = $uplatnica->attributes()['code'] ? strval($uplatnica->attributes()['code']) : '153';
        $attributes->sum = floatval($uplatnica->attributes()['sum']);
        $attributes->account = strval($uplatnica->attributes()['account']);
        $attributes->model = $uplatnica->attributes()['model'] ? strval($uplatnica->attributes()['model']) : null;
        $attributes->target = strval($uplatnica->attributes()['target']);
        
        $data->attributes = $attributes;

        return $data;
    }

    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode == 'xhtml') {
            // Check if there was an error during parsing
            if ($data->error) {
                $renderer->doc .= $this->renderErrorMessage($data->error);
                return false;
            }

            $uniqueId = str_replace('.', '-', uniqid('receipt_', true)); // добавление префикса для уникальности

            // render uplatnica (payment receipt)
            $renderer->doc .= $this->generateReceiptHtml($data->attributes, $uniqueId);

            // Load necessary JavaScript libraries for QR code generation
            $this->loadJS($renderer, $uniqueId);
        }
        return false;
    }

    private function generateReceiptHtml($attributes, $uniqueId)
    {
        $html = '<div class="receipt" id="' . $uniqueId . '">';
        $html .= '<div class="d11 col1" style="grid-area:d11">
                    <div class="data-group fullrow">
                        <div class="label"><p class="s2">уплатилац</p></div>
                        <div class="field field-payer">
                            <p class="value-editable" contenteditable="true" onkeyup="update_qr(this.id)" id="payer_' . $uniqueId . '">' . htmlspecialchars($attributes->payer) . '</p>
                        </div>
                    </div>
                  </div>';
        
        $html .= '<div class="d21 col1" style="grid-area:d21">
                    <div class="data-group fullrow">
                        <div class="label"><p class="s2">сврха уплате</p></div>
                        <div class="field field-title">
                            <p class="value" id="title_' . $uniqueId . '">' . htmlspecialchars($attributes->subject) . '</p>
                        </div>
                    </div>
                  </div>';
    
        $html .= '<div class="d31 col1" style="grid-area:d31">
                    <div class="data-group fullrow">
                        <div class="label"><p class="s2">прималац</p></div>
                        <div class="field field-recipient">
                            <p class="value" id="recipient_' . $uniqueId . '">' . htmlspecialchars($attributes->recipient) . '</p>
                        </div>
                    </div>
                  </div>';
        
        $html .= '<div class="mid-row d12 col2" style="grid-area:d12">
                    <div class="data-group">
                        <div class="label"><p class="s2">шифра</p></div>
                        <div class="field field-code">
                            <p class="value nowrap" id="code_' . $uniqueId . '">' . htmlspecialchars($attributes->code) . '</p>
                        </div>
                    </div>
                    <div class="data-group">
                        <div class="label"><p class="s2">валута</p></div>
                        <div class="field field-currency">
                            <p class="value-editable nowrap" contenteditable="false" id="currency_' . $uniqueId . '">RSD</p>
                        </div>
                    </div>
                    <div class="data-group fullrow">
                        <div class="label"><p class="s2">износ</p></div>
                        <div class="field field-sum">
                            <p class="value nowrap" id="sum_' . $uniqueId . '" onkeyup="updateTotalSum()">' . number_format($attributes->sum, 2, '.', '') . '</p>
                        </div>
                    </div>
                  </div>';
    
        $html .= '<div class="mid-row d22 col2" style="grid-area:d22">
                    <div class="data-group fullrow">
                        <div class="label"><p class="s2">рачун примаоца</p></div>
                        <div class="field field-account">
                            <p class="value nowrap" id="account_' . $uniqueId . '">' . htmlspecialchars($attributes->account) . '</p>
                        </div>
                    </div>
                  </div>';
    
        $html .= '<div class="mid-row d32 col2" style="grid-area:d32">
                    <div class="data-group">
                        <div class="label"><p class="s2">модел</p></div>
                        <div class="field field-model">
                            <p class="value nowrap" id="model_' . $uniqueId . '">' . htmlspecialchars($attributes->model) . '</p>
                        </div>
                    </div>
                    <div class="data-group fullrow">
                        <div class="label"><p class="s2">позив на број (одобрење)</p></div>
                        <div class="field field-target">
                            <p class="value nowrap" id="target_' . $uniqueId . '">' . htmlspecialchars($attributes->target) . '</p>
                        </div>
                    </div>
                  </div>';
        
        $html .= '<div class="col-qr" style="grid-area:qr">
                    <div>
                        <div class="qr-code-box">
                            <div class="qr-code-image">
                                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="532px" height="532px" viewBox="0 0 532 532" preserveAspectRatio="xMinYMin meet" role="img" aria-labelledby="qrcode-description">
                                    <description id="qrcode-description">QR code for payment</description>
                                    <rect width="100%" height="100%" fill="white" cx="0" cy="0"></rect>
                                    <!-- js-generated image -->
                                </svg>
                            </div>
                        </div>
                    </div>
                  </div>';
        
        $html .= '</div>';  // Close receipt div
        
        return $html;
    }
    

    private function renderErrorMessage($error_message)
    {
        return '<span style="font-weight: bold; color: red;">&lt;uplatnica: ' . htmlspecialchars($error_message) . ' /&gt;</span>';
    }



  // Загрузка JavaScript (подключение только один раз)
    private function loadJS(Doku_Renderer $renderer, $uniqueId)
    {
        // Подключаем скрипт для генерации QR-кодов
        $renderer->doc .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                gen_qr("' .  $uniqueId . '");
            });
        </script>';
    }

}
