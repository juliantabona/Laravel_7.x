<template>
    
    <Row>

        <Col :span="24">

            <!-- Target & Active State -->
            <Row :gutter="12" class="bg-grey-light border pt-3 pb-2 px-2 mb-3">
                
                <!-- Target -->
                <Col :span="scrollActiveSettingsExpanded ? 24 : 12" class="d-flex">
                
                    <span class="d-block font-weight-bold text-dark mt-2 mr-2">Target: </span>
                    
                    <Select v-model="pagination.content_target.selected_type" class="mb-2" placeholder="Type">
                        
                        <Option v-for="(action, key) in paginationTargets" :key="key" class="mb-2"
                                :value="action.type" :label="action.name">
                        </Option>

                    </Select>

                </Col>

                <!-- Active State -->
                <Col :span="scrollActiveSettingsExpanded ? 24 : 12" class="mb-3">

                    <!-- Show active state checkbox (Marks if this is active / inactive) -->
                    <activeStateCheckbox v-model="pagination.active" sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"></activeStateCheckbox>
                
                </Col>

            </Row>

            <!-- Content Separation Settings -->
            <Row :gutter="12" class="bg-grey-light border pt-3 pb-2 px-2 mb-3">

                <!-- Separation Type -->
                <Col :span="24" class="d-flex mb-2">
                
                    <span class="d-block font-weight-bold text-dark mt-1" style="width: 170px;">Separation Type: </span>
                    
                    <Select v-model="pagination.slice.separation_type" class="mb-2" placeholder="Type">
                        
                        <Option v-for="(action, key) in separationTypes" :key="key" class="mb-2"
                                :value="action.type" :label="action.name">
                        </Option>

                    </Select>

                </Col>

                <Col :span="24" class="d-flex">

                    <!-- Enable / Disable Pagination By Line Breaks -->
                    <Checkbox v-model="pagination.paginate_by_line_breaks">Don't cut select options or line breaks</Checkbox>

                </Col>

            </Row>

            <!-- Scroll Down Settings -->
            <Row :gutter="12" class="bg-grey-light border pt-3 pb-2 px-2 mb-3">

                <!-- Scroll Down Name -->
                <Col :span="scrollDownSettingsExpanded ? 24 : 12">
                
                    <template v-if="pagination.scroll_down.visible">
                
                        <textOrCodeEditor
                            class="mb-2"
                            size="small"
                            title="Scroll Down Name"
                            :placeholder="'99. Next'"
                            icon="ios-arrow-round-down"
                            :value="pagination.scroll_down.name"
                            sampleCodeTemplate="ussd_service_instructions_sample_code">
                        </textOrCodeEditor>

                    </template>
                
                    <!-- Enable / Disable Scroll Down Visibility -->
                    <Checkbox v-model="pagination.scroll_down.visible" class="mb-2">Show scroll down name</Checkbox>
                
                </Col>
                
                <!-- Scroll Down Input -->
                <Col :span="scrollDownSettingsExpanded ? 24 : 12" class="mt-2">

                    <textOrCodeEditor
                        class="mb-3"
                        size="small"
                        :placeholder="'99'"
                        title="Scroll Down Input"
                        :value="pagination.scroll_down.input"
                        sampleCodeTemplate="ussd_service_instructions_sample_code">
                    </textOrCodeEditor>
                
                </Col>

            </Row>

            <!-- Scroll Up Settings -->
            <Row :gutter="12" class="bg-grey-light border pt-3 pb-2 px-2 mb-3">

                <!-- Scroll Up Name -->
                <Col :span="scrollUpSettingsExpanded ? 24 : 12">

                    <template v-if="pagination.scroll_up.visible">
                        
                        <textOrCodeEditor
                            class="mb-2"
                            size="small"
                            title="Scroll Up Name"
                            :placeholder="'88. Prev'"
                            icon="ios-arrow-round-up"
                            :value="pagination.scroll_up.name"
                            sampleCodeTemplate="ussd_service_instructions_sample_code">
                        </textOrCodeEditor>

                    </template>
                
                    <!-- Enable / Disable Scroll Up Visibility -->
                    <Checkbox v-model="pagination.scroll_up.visible" class="mb-2">Show scroll up name</Checkbox>
                
                </Col>
                
                <!-- Scroll Up Input -->
                <Col :span="scrollUpSettingsExpanded ? 24 : 12" class="mt-2">

                    <textOrCodeEditor
                        class="mb-3"
                        size="small"
                        :placeholder="'88'"
                        title="Scroll Up Input"
                        :value="pagination.scroll_up.input"
                        sampleCodeTemplate="ussd_service_instructions_sample_code">
                    </textOrCodeEditor>
                
                </Col>

            </Row>

            <!-- Content Slicing & Trailing Settings -->
            <Row :gutter="12" class="bg-grey-light border pt-3 pb-2 px-2 mb-3">

                <Col :span="24" class="mb-3">

                    <!-- Start Position -->
                    <textOrCodeEditor
                        class="mb-3"
                        size="small"
                        :placeholder="'0'"
                        title="Start Slice Position"
                        :value="pagination.slice.start"
                        sampleCodeTemplate="ussd_service_instructions_sample_code">
                    </textOrCodeEditor>

                    <!-- End Position -->
                    <textOrCodeEditor
                        class="mb-3"
                        size="small"
                        :placeholder="'160'"
                        title="End Slice Position"
                        :value="pagination.slice.end"
                        sampleCodeTemplate="ussd_service_instructions_sample_code">
                    </textOrCodeEditor>

                    <!-- Trailing Characters -->
                    <textOrCodeEditor
                        class="mb-3"
                        size="small"
                        :placeholder="'...'"
                        title="Trailing Characters"
                        :value="pagination.trailing_end"
                        sampleCodeTemplate="ussd_service_instructions_sample_code">
                    </textOrCodeEditor>
                
                </Col>

                <!-- Break line before trail -->
                <Col :span="12" class="mb-3">

                    <!-- Enable / Disable Break Line Before Trail -->
                    <Checkbox v-model="pagination.break_line_before_trail">Break line before trail</Checkbox>
                
                </Col>

                <!-- Break line after trail -->
                <Col :span="12" class="mb-3">

                    <!-- Enable / Disable Break Line After Trail -->
                    <Checkbox v-model="pagination.break_line_after_trail">Break line after trail</Checkbox>
                
                </Col>

            </Row>
            
        </Col>

    </Row>

</template>

<script>

    import textOrCodeEditor from './../../../textOrCodeEditor.vue';
    import activeStateCheckbox from './../../../activeStateCheckbox.vue';
    import customEditor from './../../../../../../../../../../../../components/_common/wysiwygEditors/customEditor.vue';

    export default {
        components: { textOrCodeEditor, activeStateCheckbox, customEditor },
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
            }
        },
        data(){
            return{
                pagination: this.display.content.pagination,
                separationTypes: [
                    {
                        name: 'Words', type: 'words'
                    },
                    {
                        name: 'Characters', type: 'characters'
                    }
                ],
                paginationTypes: [
                    {
                        name: 'Scroll Up', type: 'scroll_up'
                    },
                    {
                        name: 'Scroll Down', type: 'scroll_down'
                    }
                ],
                paginationTargets: [
                    {
                        name: 'Instruction Content', type: 'instruction'
                    },
                    {
                        name: 'Action Content', type: 'action'
                    },
                    {
                        name: 'Both', type: 'both'
                    }
                ]
            }
        },
        computed: {
            scrollActiveSettingsExpanded(){
                return ( this.pagination.active.code_editor_mode );
            },
            scrollUpSettingsExpanded(){
                return ( ( this.pagination.scroll_up.name.code_editor_mode && this.pagination.scroll_up.visible) || 
                           this.pagination.scroll_up.input.code_editor_mode );
            },
            scrollDownSettingsExpanded(){
                return ( ( this.pagination.scroll_down.name.code_editor_mode && this.pagination.scroll_down.visible) || 
                           this.pagination.scroll_down.input.code_editor_mode );
            }
        },
        created(){

        }
    }
</script>