<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
function load_matrix_console()
{
    global $config;

    if (! check_acl($config['id_user'], 0, 'ER')) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access event viewer'
        );
        include 'general/noaccess.php';
        return;
    }

    $pure = (bool) get_parameter('pure');

    if (! $pure) {
        $title_menu = __('Matrix events');
        $fullscreen['text'] = '<a href="index.php?extension_in_menu=eventos&sec=extensions&sec2=extensions/matrix_events&pure=1">'.html_print_image(
            'images/full_screen.png',
            true,
            [
                'title' => __('Full screen mode'),
                'class' => 'invert_filter',
            ]
        ).'</a>';
        $onheader = ['fullscreen' => $fullscreen];
        ui_print_page_header($title_menu, 'images/op_monitoring.png', false, '', false, $onheader);
    }

    echo '<canvas id="matrix-terminal" class="visible"></canvas>';

    ?>
    <script language="javascript" type="text/javascript">

        var terminal = {
            element : "",
            context : null,
            fontSize : 14,
            timers : {
                rain : null,
                event : null,
                events : null
            },
            stopRain : function () {
                if (this.timers.rain) {
                    clearInterval(this.timers.rain);
                }
            },
            stopEvent : function () {
                if (this.timers.event) {
                    clearInterval(this.timers.event);
                }
            },
            clear : function () {
                this.stopRain();
                this.stopEvent();
                if (this.context) {
                    this.context.fillStyle = "rgba(0, 0, 0, 1.0)";
                    this.context.fillRect(0, 0, width, height);
                }
            },
            print : function () {

                this.stopRain();

                var letters = "田由甲申甴电甶ｱｲｳｴｵｶｷｸｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝ<>*01ACREJWH{V";

                // Converting the string into an array of single characters
                letters = letters.split("");

                var width = this.element.width;
                var height = this.element.height;
                var context = this.context;

                var font_size = this.fontSize;
                // Number of columns for the rain
                var columns = Math.round(width / font_size);
                // Number of rows for the rain
                var rows = Math.round(height / font_size);
                // An array of drops - one per column
                var drops = [];
                // x below is the x coordinate
                // 1 = y co-ordinate of the drop(same for every drop initially)
                for (var x = 0; x < columns; x++) {
                    drops[x] = -1;
                }

                // Drawing the characters
                function draw() {
                    // Black BG for the canvas
                    // Translucent BG to show trail
                    context.fillStyle = "rgba(0, 0, 0, 0.05)";
                    context.fillRect(0, 0, width, height);
                    
                    context.fillStyle = "#0F0"; // Green text
                    context.font = font_size + "px lato";
                    // Looping over drops
                    for (var i = 0; i < drops.length; i++) {
                        if (drops[i] == -1) {
                            if (Math.random() > 0.975) {
                                drops[i] = 1;
                            } else {
                                continue;
                            }
                        }
                        // A random character to print
                        var letter = letters[Math.floor(Math.random() * letters.length)];
                        //x = i * font_size, y = value of drops[i] * font_size
                        context.fillText(letter, i * font_size, drops[i] * font_size);
                        
                        // Sending the drop back to the top randomly after it has crossed the screen
                        // Adding a randomness to the reset to make the drops scattered on the Y axis
                        if (drops[i] * font_size > height && Math.random() > 0.975) {
                            drops[i] = 0;
                        }
                        //incrementing Y coordinate
                        drops[i]++;
                    }

                }
                this.timers.rain = setInterval(draw, 33);
            },
            showEvent : function (message) {

                this.stopEvent();

                var context = this.context;
                var font_size = this.fontSize;
                var width = this.element.width;
                var height = this.element.height;
                var columns = Math.round(width / font_size);
                var rows = Math.round(height / font_size);
                var middleColumn = Math.floor(rows / 2);
                
                while (message.length < columns) {
                    message = " " + message + " ";
                }

                // Converting the string into an array of single characters
                var letters = message.split("");
                var positions = [];
                // x below is the x coordinate
                // 1 = y co-ordinate of the drop(same for every drop initially)
                for (var i = 0; i < letters.length; i++) {
                    positions[i] = -1;
                }
                function drawMessage() {
                    // Looping over drops
                    for (var i = 0; i < letters.length; i++) {
                        if (positions[i] < 0) {
                            if (Math.random() > 0.9) {
                                positions[i] = 1;
                            } else {
                                continue;
                            }
                        } else if (positions[i] < middleColumn) {
                            context.fillStyle = "#0F0";
                            context.fillText(letters[i], i * font_size, positions[i] * font_size);
                            //incrementing Y coordinate
                            positions[i]++;
                        } else if (positions[i] > middleColumn) {
                            context.fillStyle = "#0F0";
                            context.fillText(letters[i], i * font_size, positions[i] * font_size);
                            //incrementing Y coordinate
                            // Adding a randomness to the reset to make the drops scattered on the Y axis
                            if (positions[i] * font_size > terminal.height) {
                                positions[i] = -1;
                            }
                            //incrementing Y coordinate
                            positions[i]++;
                        } else {
                            context.fillStyle = "rgba(255, 255, 255, 1.0)";
                            context.fillText(letters[i], i * font_size, positions[i] * font_size);

                            function moveLetter(array, index) {
                                setTimeout(function() {
                                    if (array[index] == middleColumn) {
                                        array[index]++;
                                    }
                                }, 15000);
                            }
                            moveLetter(positions, i);
                        }
                    }
                }
                this.timers.event = setInterval(drawMessage, 100);
            }
        }
        
        $(document).ready (function () {

            var pure = <?php echo json_encode($pure); ?>;
            
            terminal.element = document.getElementById("matrix-terminal");
            if (pure) {
                terminal.fontSize = terminal.fontSize + 2;
                terminal.element.height = window.innerHeight;
                terminal.element.width = window.innerWidth;

                $("#matrix-terminal").click(function() {
                    document.location.search = "?extension_in_menu=eventos&sec=extensions&sec2=extensions/matrix_events";
                });
                $("#main_pure").css("margin", 0)
            } else {
                terminal.element.height = $("#main").innerHeight();
                terminal.element.width = $("#main").innerWidth();

                $("#matrix-terminal").dblclick(function() {
                    document.location.search = "?extension_in_menu=eventos&sec=extensions&sec2=extensions/matrix_events&pure=1";
                });
            }
            terminal.context = terminal.element.getContext("2d");

            terminal.print();
            showLastEvents();
        });

        // Shows the last 5 events via ajax
        function showLastEvents () {

            if (terminal.timers.events) {
                clearInterval(terminal.timers.events);
            }
            
            $.ajax ({
                url : "ajax.php",
                data : {
                    page: "<?php echo EXTENSIONS_DIR; ?>/matrix_events/ajax",
                    get_last_events: 1
                },
                type : 'POST',
                dataType : 'json',
                success: function (data) {
                    events = data;

                    if (events.length > 0) {
                        event = events.shift();
                        terminal.showEvent(event.agent + " - " + event.text + " - " + event.date);
                    }
                    var showEventsInterval = function () {
                        if (events.length > 0) {
                            event = events.shift();
                            terminal.showEvent(event.agent + " - " + event.text + " - " + event.date);
                        } else {
                            showLastEvents();
                        }
                    }
                    terminal.timers.events = setInterval(showEventsInterval, 20000);
                }
            });
        }
    </script>
    <?php
}


extensions_add_operation_menu_option('Matrix', 'eventos', '', 'v1r1');
extensions_add_main_function('load_matrix_console');

