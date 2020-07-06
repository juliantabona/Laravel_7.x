<template>

    <div class="d-flex">

        <div>

            <span class="font-weight-bold mt-1">{{ title }}</span>
        
            <Poptip trigger="hover" width="350" placement="right" word-wrap>

                <template slot="content">
                    <span class="d-block">Use the Code Editor to write code in PHP</span>
                
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
                    :value="value.code_editor_mode" 
                    @on-change="value.code_editor_mode = $event">
                </i-switch>

            </Poptip>

        </div>

        <template v-if="value.code_editor_mode">

            <!-- Code Editor -->
            <customEditor
                :useCodeEditor="true"
                :sampleCodeTemplate="sampleCodeTemplate"
                :codeContent="value.code_editor_text"
                @codeChange="value.code_editor_text = $event">
            </customEditor>

        </template>
 
        <!-- Show active state checkbox (Marks if this is active / inactive) -->
        <Checkbox v-else v-model="value.text" class="ml-2">Active</Checkbox>

    </div>

</template>

<script>

    import customEditor from './../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { customEditor },
        props: {
            size: {
                type: String,
                default: null
            },
            title: {
                type: String,
                default: 'Active:'
            },
            value: {
                type: Object,
                default: null
            },
            sampleCodeTemplate: {
                type: String,
                default: null
            }
        },
        data(){
            return {
                
            }
        }
    }
</script>
