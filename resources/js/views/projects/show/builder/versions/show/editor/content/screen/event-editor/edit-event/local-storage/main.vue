<template>

    <div>

        <!-- Local Storage Settings -->
        <div class="mt-2">

            <!-- Validation Rules Instruction -->
            <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
                Use <span class="font-italic text-success font-weight-bold">Local Storage</span> to store
                information as Arrays or Strings. This is useful especially when using repeating screens
                and displays that collect information from the user.
            </Alert>

            <!-- Reference Name Input -->
            <referenceNameInput 
                class="mb-2"
                v-model="event.event_data.reference_name"
                :builder="builder" :screen="screen" :display="display"
                title="Reference:" :inlineLayout="false" :isRequired="false">
            </referenceNameInput>

            <!-- Storage Settings -->
            <Row :gutter="20" class="bg-grey-light p-2 mx-0 mb-2">
                
                <!-- Storage Type (e.g Array, String, Code) -->
                <Col :span="12" class="mb-2">

                    <div class="d-flex">

                        <span class="font-weight-bold text-dark mt-1 mr-2">Storage:</span>
                                
                        <!-- Storage Method -->
                        <Select v-model="event.event_data.storage.selected_type">
                            <Option v-for="(storage_type, index) in storage_types" :value="storage_type.value" :key="index">
                                {{ storage_type.name }}
                            </Option>
                        </Select>
                    
                    </div>

                </Col>
                   
                <!-- Structure Type (e.g Values, Key Values) -->
                <template v-if="event.event_data.storage.selected_type == 'array'">

                    <Col :span="12" class="mb-2">

                        <div class="d-flex">

                            <span class="font-weight-bold text-dark mt-1 mr-2">Structure:</span>

                            <!-- Array Data Type Storage -->
                            <Select v-model="event.event_data.storage.array.dataset.selected_type">
                                <Option v-for="(dataset, index) in array_datasets" :value="dataset.value" :key="index">
                                    {{ dataset.name }}
                                </Option>
                            </Select>

                        </div>

                    </Col>

                </template>
                
                <!-- Mode (e.g Append, Prepend, Replace) -->
                <Col :span="12" class="mb-2">

                    <div class="d-flex">

                        <span class="font-weight-bold text-dark mt-1 mr-2">Mode:</span>
                        
                        <template v-if="event.event_data.storage.selected_type == 'array'">

                            <!-- Array Mode -->
                            <Select v-model="event.event_data.storage.array.mode.selected_type">
                                <Option v-for="(mode, index) in array_modes" :value="mode.value" :key="index">
                                    {{ mode.name }}
                                </Option>
                            </Select>

                        </template>

                        <template v-if="event.event_data.storage.selected_type == 'string'">

                            <!-- String Mode -->
                            <Select v-model="event.event_data.storage.string.mode.selected_type">
                                <Option v-for="(mode, index) in string_modes" :value="mode.value" :key="index">
                                    {{ mode.name }}
                                </Option>
                            </Select>

                        </template>

                        <template v-if="event.event_data.storage.selected_type == 'code'">

                            <!-- Code Mode -->
                            <Select v-model="event.event_data.storage.code.mode.selected_type">
                                <Option v-for="(mode, index) in code_modes" :value="mode.value" :key="index">
                                    {{ mode.name }}
                                </Option>
                            </Select>

                        </template>

                    </div>

                </Col>
                
                <!-- Separator (e.g ",") -->
                <Col :span="12" class="mb-2">

                    <div v-if="event.event_data.storage.string.mode.selected_type == 'concatenate'" class="d-flex">

                        <span class="font-weight-bold text-dark mt-1 mr-2">Join:</span>

                        <!-- Join Value -->
                        <customEditor
                            size="small"
                            :placeholder="','"
                            :useCodeEditor="false"
                            :content="event.event_data.storage.string.mode.concatenate.value"
                            @contentChange="event.event_data.storage.string.mode.concatenate.value = $event">
                        </customEditor>

                        <br/>Join: {{ event.event_data.storage.string.mode.concatenate.value }}

                    </div>

                </Col>

            </Row>

            <!-- Storage Values -->
            <Row :gutter="4">

                <Col :span="24">

                    <!-- Array Storage -->
                    <template v-if="event.event_data.storage.selected_type == 'array'">

                        <template v-if="event.event_data.storage.array.dataset.selected_type == 'values'">
                            
                            <!-- Key/Value Manager -->
                            <arrayValueStorageManager v-bind="$props"></arrayValueStorageManager>

                        </template>

                        <template v-if="event.event_data.storage.array.dataset.selected_type == 'key_values'">

                            <!-- Key/Value Manager -->
                            <arrayKeyValueStorageManager v-bind="$props"></arrayKeyValueStorageManager>

                        </template>

                    </template>

                    <!-- String Storage -->
                    <template v-if="event.event_data.storage.selected_type == 'string'">

                        <!-- String Manager -->
                        <stringStorageManager v-bind="$props"></stringStorageManager>

                    </template>

                    <template v-if="event.event_data.storage.selected_type == 'code'">
                        
                        <!-- Code Manager -->
                        <codeStorageManager v-bind="$props"></codeStorageManager>

                    </template>

                </Col>

            </Row>

        </div>

    </div>

</template>

<script>

    var localCustomMixin = require('./localMixin.js').default;

    import codeStorageManager from './code-manager/main.vue';
    import stringStorageManager from './string-manager/main.vue';
    import arrayValueStorageManager from './array-value-manager/main.vue';
    import arrayKeyValueStorageManager from './array-key-value-manager/main.vue'
    
    import textOrCodeEditor from './../../../textOrCodeEditor.vue';
    import referenceNameInput from './../../../referenceNameInput.vue';;
    import customEditor from './../../../../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { 
            codeStorageManager, stringStorageManager, arrayValueStorageManager, arrayKeyValueStorageManager,
            textOrCodeEditor, referenceNameInput, customEditor
        },
        mixins: [localCustomMixin],
        props: { 
            event: {
                type: Object,
                default:() => {}
            },
            events: {
                type: Array,
                default: () => []
            },
            display: {
                type: Object,
                default:() => {}
            },
            screen: {
                type: Object,
                default:() => {}
            },
            builder: {
                type: Object,
                default: () => {}
            },
        },
        data(){
            return{
                
            }
        }, 
        computed: {

        },
        methods: {
            updateArrayValue(index, updatedValue){
                this.$set(this.event.event_data.storage.array.dataset.values, index, updatedValue);
            },
            addArrayValue(){
                this.event.event_data.storage.array.dataset.values.push({
                    value: {
                        text: '',                             //  e.g "{{ product.quantity }}"
                        code_editor_text: '',
                        code_editor_mode: false
                    },
                    on_empty_value: {
                        selected_type: 'nullable',            //  default, nullable
                        default: {
                            selected_type: 'custom',          //  text_input, number_input, true, false, null, empty_array
                            custom: {
                                text: '',                     //  e.g "{{ product.quantity }}"
                                code_editor_text: '',
                                code_editor_mode: false
                            },
                        }
                    }
                });
            },
            addArrayKeyValue(){
                
                this.event.event_data.storage.array.dataset.key_values.push({
                    key: '',
                    value: {
                        text: '',                             //  e.g "{{ product.quantity }}"
                        code_editor_text: '',
                        code_editor_mode: false
                    },
                    on_empty_value: {
                        selected_type: 'nullable',            //  default, nullable
                        default: {
                            selected_type: 'custom',          //  text_input, number_input, true, false, null, empty_array
                            custom: {
                                text: '',                     //  e.g "{{ product.quantity }}"
                                code_editor_text: '',
                                code_editor_mode: false
                            },
                        }
                    }
                });

            },
            removeArrayValue(index){
                this.event.event_data.storage.array.dataset.values.splice(index, 1);
            },
            removeArrayKeyValue(index){
                this.event.event_data.storage.array.dataset.key_values.splice(index, 1);
            }
        },
        created(){

        }
    }
</script>