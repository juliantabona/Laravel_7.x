<template>

    <div class="d-flex">

        <span class="font-weight-bold mt-1 mr-1">Link:</span>
        
        <Poptip trigger="hover" width="350" placement="right" class="mt-1 mr-2" word-wrap>

            <template slot="content">
                <span class="d-block">Use the Code Editor to write code in PHP to conditionally select the next screen/display</span>
            
                <span style="margin-top: -15px;" class="border-top d-block pt-2">
                    <span class="font-weight-bold">Note:</span> Always wrap strings in <span class="text-primary">single quotes ('')</span> and use the <span class="text-primary">period (.)</span> to concatenat values. Use <span class="text-primary">double pipe (||)</span> or <span class="text-primary">("\n")</span> for line-breaks. Make sure to include the <span class="text-primary">return</span> statement as soon as you want to output your result. Keep all your code within PHP Tags <span class="text-primary"><?php ?></span>
                </span>
            </template>
            
            <i-switch 
                size="small"
                class="ml-1"
                :disabled="false"
                true-color="#13ce66" 
                false-color="#ff4949" 
                :value="link.code_editor_mode" 
                @on-change="link.code_editor_mode = $event">
            </i-switch>

        </Poptip>

        <!-- Screen /  Display Code Selector -->
        <customEditor
            :useCodeEditor="true"
            v-if="link.code_editor_mode"
            :codeContent="link.code_editor_text"
            @codeChange="link.code_editor_text = $event"
            :placeholder="'return \'screen_1592485908654\'; '"
            sampleCodeTemplate="ussd_service_screen_or_display_link_sample_code">
        </customEditor>
        
        <!-- Screen /  Display Cascader Selector -->
        <Cascader v-else v-model="selectedOption" 
                  :data="options" trigger="hover" placeholder="Link" 
                  @on-change="updateLink($event)">
        </Cascader>

    </div>

</template>

<script>

    import customEditor from './../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { customEditor },
        props: {
            screen: {
                type: Object,
                default: null
            },
            display: {
                type: Object,
                default: null
            },
            builder: {
                type: Object,
                default: null
            },
            link: {
                type: Object,
                default: null
            },
            showScreens: {
                type: Boolean,
                default: true
            },
            showDisplays: {
                type: Boolean,
                default: true
            }
        },
        data(){
            return {
                selectedOption: []
            }
        },
        computed: {
            options(){

                var options = [];

                const screen = this.screen;
                const display = this.display;

                //  If we are allowed to show the screens
                if( this.showScreens ){

                    var screens = this.builder.screens.map(function(currentScreen, index){

                        return {
                            value: currentScreen.id,
                            label: currentScreen.name,
                            disabled: currentScreen.id == (screen || {}).id
                        }

                    });

                    //  Add the list of screens
                    options.push({
                        value: 'screens',
                        label: 'Screens',
                        children: screens
                    });

                }
                
                //  If we are allowed to show the current screen displays
                if( this.showDisplays && this.screen ){

                    //  Get the current
                    var displays = this.screen.displays.map(function(currentDisplay, index){

                        return {
                            value: currentDisplay.id,
                            label: currentDisplay.name,
                            disabled: currentDisplay.id == (display || {}).id
                        }

                    });

                    //  Add the list of displays
                    options.push({
                        value: 'displays',
                        label: 'Displays',
                        children: displays
                    });

                }
                
                return options;

            }
        },
        methods: {
            getSelectedOption(){
                var isScreen = this.link.text.startsWith('screen');
                var isDisplay = this.link.text.startsWith('display');

                //  If this is a screen id
                if( isScreen ){

                    //  Select the screen on the cascader
                    return ['screens', this.link.text];

                //  If this is a display id
                }else if( isDisplay ){

                    //  Select the display on the cascader
                    return ['displays', this.link.text];

                }

                return [];

            },
            updateLink( selectedOption ){
                if( selectedOption[1] ){
                    this.$set(this.link, 'text', selectedOption[1]);
                }
            }
        },
        created(){
            this.selectedOption = this.getSelectedOption();
        }
    }
</script>
