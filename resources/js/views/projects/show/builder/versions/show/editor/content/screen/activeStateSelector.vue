<template>


    <div >

        <div class="d-flex">

            <span class="font-weight-bold mt-1 mr-2">{{ title }}</span> 

            <Select v-model="value.selected_type">
                <Option v-for="(activeStateOption, index) in activeStateOptions" 
                        :value="activeStateOption.value" :key="index">
                    {{ activeStateOption.name }}
                </Option>
            </Select>
            
        </div>

        <template v-if="value.selected_type == 'conditional'">

            <!-- Code Editor -->
            <customEditor
                class="mt-2"
                :useCodeEditor="true"
                :codeContent="value.code"
                @codeChange="value.code = $event"
                sampleCodeTemplate="ussd_service_instructions_sample_code">
            </customEditor>

        </template>

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
                activeStateOptions: [
                    {
                        name: 'Yes',
                        value: 'yes'
                    },
                    {
                        name: 'No',
                        value: 'no'
                    },
                    {
                        name: 'Conditional',
                        value: 'conditional'
                    }
                ]
            }
        }
    }
</script>
