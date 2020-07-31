<template>

    <div>

        <!-- Header -->
        <span class="d-block font-weight-bold text-dark">Responses</span>

        <!-- General Details i.e Default Messages -->
        <div class="bg-grey-light border mt-2 mb-3 p-2">

            <!-- Default API Success -->
            <textOrCodeEditor
                class="mb-2"
                size="small"
                :value="event.event_data.response.general.default_success_message"
                sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"
                :placeholder="'Hey {{ first_name }}, thank you for signing up!'">
            
                <!-- Sub Heading -->
                <template v-slot:title>
                    <span class="d-block text-dark">
                        <span class="font-weight-bold">Success </span>(Default Message)
                    </span>
                </template>

            </textOrCodeEditor>

            <!-- Default API Error -->
            <textOrCodeEditor
                class="mb-2"
                size="small"
                :value="event.event_data.response.general.default_error_message"
                sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"
                :placeholder="'Sorry {{ first_name }}, we are experiencing technical difficulties'">

                <!-- Sub Heading -->
                <template v-slot:title>
                    <span class="d-block text-dark">
                        <span class="font-weight-bold">Error </span>(Default Message)
                    </span>
                </template>

            </textOrCodeEditor>

        </div>

        <!-- Select response type i.e Automatic / Manual -->
        <div class="d-flex">

            <span class="font-weight-bold text-dark mt-1 mr-2">Use:</span>

            <Select v-model="event.event_data.response.selected_type" 
                    placeholder="Select" class="mb-4">

                <Option 
                    v-for="(api_response_type, key) in apiResponseTypes"
                    :key="key" :value="api_response_type.value" 
                    :label="api_response_type.name">
                </Option>

            </Select>

        </div>

        <!-- Manual response -->
        <template v-if="event.event_data.response.selected_type == 'manual'">

            <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
                Add one or more <span class="font-italic text-success font-weight-bold">Status Codes</span> to take advantage of dynamic screen content. 
                Each status code represents an opportunity to handle the request in a specific manner e.g fetching the response data and displaying it
                as dynamic <span class="font-italic text-success font-weight-bold">Select Options</span>  on a status
                <span class="font-italic text-success font-weight-bold">200</span>, or displaying the 
                <span class="font-italic text-success font-weight-bold">Api Error</span> to the user on a status
                <span class="font-italic text-danger font-weight-bold">400</span>.
            </Alert>

            <div class="clearfix">

                <!-- Add Response Status Code Button -->
                <basicButton :type="addButtonType" size="default" icon="ios-add" :showIcon="true"
                             class="float-right" iconDirection="left" 
                             :ripple="!responseStatusHandlesExist"
                             @click.native="addStatusCodeHandle()">
                    <span>Add Option</span>
                </basicButton>

            </div>

             <template v-if="responseStatusHandlesExist">

                <Tabs v-model="activeNavTab" :key="renderKey" type="card" style="overflow: visible;" 
                     :animated="false" closable @on-tab-remove="handleTabRemoved($event)">

                    <!-- Response Status Code Navigation Tabs -->
                    <TabPane v-for="(responseStatusHandle, key) in event.event_data.response.manual.response_status_handles" 
                             :key="key" :label="responseStatusHandle.status" :name="responseStatusHandle.status">

                        <!-- Reference Name Input -->
                        <referenceNameInput
                            class="mb-2"
                            v-model="responseStatusHandle.reference_name"
                            :builder="builder" :screen="screen" :display="display"
                            title="Response reference name:" :inlineLayout="false">
                        </referenceNameInput>

                        <!-- Response Attributes -->
                        <div class="bg-grey-light border mt-3 mb-3 p-2">

                            <Row :gutter="4">

                                <Col :span="11">
                            
                                    <span class="d-block font-weight-bold text-dark mb-2">Attribute Name</span>

                                </Col>

                                <Col :span="13">
                            
                                    <span class="d-block font-weight-bold text-dark mb-2">Response Target</span>

                                </Col>

                            </Row>

                            <Row v-for="(attribute, index) in responseStatusHandle.attributes" :key="index" :gutter="4" class="mb-2">
                                
                                <Col :span="11">

                                    <!-- Multi Value Reference Name Input -->
                                    <referenceNameInput 
                                        :referenceNames="getAttributeReferenceNames(responseStatusHandle.attributes)"
                                        :placeholder="getAttributePlaceholder(responseStatusHandle, index, 0)"
                                        v-model="attribute.name"
                                        :isRequired="false"
                                        :display="display"
                                        :builder="builder"
                                        :screen="screen"
                                        :index="index"
                                        size="small">
                                    </referenceNameInput>

                                </Col>

                                <Col :span="11">

                                    <Input v-model="attribute.value"
                                        type="text" class="w-100" size="small"
                                        :placeholder="getAttributePlaceholder(responseStatusHandle, index, 1)">
                                    </Input>

                                </Col>

                                <Col :span="2" class="clearfix">

                                    <!-- Remove Option Button  -->
                                    <Poptip confirm title="Are you sure you want to remove this attribute?" 
                                            ok-text="Yes" cancel-text="No" width="300" @on-ok="removeResponseOption(responseStatusHandle, index)"
                                            placement="top-end" class="float-right">
                                        <Icon type="ios-trash-outline" size="20"/>
                                    </Poptip>

                                </Col>

                            </Row>

                            <div class="clearfix">

                                <!-- Run Api -->
                                <Button class="float-right" @click.native="addResponseOption(responseStatusHandle)">
                                    <Icon type="ios-add" :size="20" />
                                    <span>Add</span>
                                </Button>

                            </div>

                        </div>

                        <!-- On Handle -->
                        <div class="d-flex mb-3">
                            <span class="font-weight-bold text-dark mt-1 mr-2">After Response:</span>
                            <Select v-model="responseStatusHandle.on_handle.selected_type" style="width: 200px;">

                                <Option 
                                    v-for="(option, key) in manualOptions"
                                    :key="key" :value="option.value" :label="option.name">
                                </Option>

                            </Select>
                        </div>
                        
                        <template v-if="responseStatusHandle.on_handle.selected_type == 'use_custom_msg'">

                            <!-- Display Custom Message Explainer -->
                            <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
                                Write a <span class="font-italic text-success font-weight-bold">Custom Message</span>. This is the message
                                that will be returned if the request returns a status <span :class="'font-italic '+getErrorOrSuccessClass(responseStatusHandle)+' font-weight-bold'">{{ responseStatusHandle.status }}</span>.
                            </Alert>

                            <!-- Custom Message -->
                            <textOrCodeEditor
                                class="mb-2"
                                size="medium"
                                icon="ios-code"
                                title="Custom Message"
                                :value="responseStatusHandle.on_handle.use_custom_msg"
                                sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"
                                :placeholder="getCustomMessagePlaceholder(responseStatusHandle)">
                            </textOrCodeEditor>

                        </template>

                    </TabPane>

                </Tabs>
                
            </template>

            <!-- Add status code handle message -->
            <Alert v-else type="info" class="mt-2 mb-2" show-icon>Add status codes to handle</Alert>

        </template>

        <template v-else>

            <!-- Automatic response -->
            <Alert type="info" style="line-height: 1.4em;" class="mb-2">
                The application will automatically decide whether to use the <span class="font-italic text-success font-weight-bold">default success</span> or
                <span class="font-italic text-success font-weight-bold">default error</span> message depending on whether or not the API call is successful.
            </Alert>

            <Row :gutter="10">

                <!-- On Success Handle -->
                <Col :span="12" class="d-flex">
                    <span class="font-weight-bold text-dark mt-1 mr-2">On Success:</span>
                    <Select v-model="event.event_data.response.automatic.on_handle_success" style="width: 200px;">

                        <Option 
                            v-for="(option, key) in automaticSuccessOptions"
                            :key="key" :value="option.value" :label="option.name">
                        </Option>

                    </Select>
                </Col>

                <!-- On Fail Handle -->
                <Col :span="12" class="d-flex">
                    <span class="font-weight-bold text-dark mt-1 mr-2">On Fail:</span>
                    <Select v-model="event.event_data.response.automatic.on_handle_error" style="width: 200px;">

                        <Option 
                            v-for="(option, key) in automaticErrorOptions"
                            :key="key" :value="option.value" :label="option.name">
                        </Option>

                    </Select>
                </Col>

            </Row>

        </template>

        <!-- 
             MODAL TO ADD NEW STATUS CODE HANDLE
        -->
        <template v-if="isOpenAddStatusCodeHandle">

            <addStatusCodeHandle
                :statusCodeHandles="event.event_data.response.manual.response_status_handles"
                @createdStatusHandle="handleCreatedStatusHandle($event)"
                @visibility="isOpenAddStatusCodeHandle = $event">
            </addStatusCodeHandle>

        </template>

    </div>

</template>

<script>

    import basicButton from './../../../../../../../../../../../../../../components/_common/buttons/basicButton.vue';
    import referenceNameInput from './../../../../../referenceNameInput.vue';
    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';
    import addStatusCodeHandle from './addStatusCodeHandle';

    var localCustomMixin = require('./localMixin.js').default;

    export default {
        components: { basicButton, referenceNameInput, textOrCodeEditor, addStatusCodeHandle },
        mixins: [localCustomMixin],
        props:{
            index: {
                type: Number,
                default: null
            },
            event: {
                type: Object,
                default: null
            },
            events: {
                type: Array,
                default: () => []
            },
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
            isCloning: {
                type: Boolean,
                default: false
            },
            isEditing: {
                type: Boolean,
                default: false
            },
        },
        data(){
            return{
                renderKey: 1,
                isOpenAddStatusCodeHandle: false,
                activeNavTab: this.getFirstTabStatus(),
                /** Note:
                 * 
                 *  The following data properties: "automaticSuccessOptions", "automaticErrorOptions", 
                 *  "manualOptions", "apiResponseTypes" are defined within "localCustomMixin"
                 */
            }
        },

        computed: {

            //  Check if the response status handles exist
            responseStatusHandlesExist(){

                return (this.event.event_data.response.manual.response_status_handles.length) ? true : false ;

            },
            addButtonType(){
                return this.responseStatusHandlesExist ? 'primary' : 'success';
            },

        },

        methods: {
            getAttributeReferenceNames(attributes){
                return attributes.map((attribute) => {
                    return attribute.name;
                });
            },
            getAttributePlaceholder(responseStatusHandle, index, value){

                if( this.isGoodStatus(responseStatusHandle.status) ){

                    var examples = [
                        ['products', '{{ '+responseStatusHandle.reference_name+'.products }}'],
                        ['orders', '{{ '+responseStatusHandle.reference_name+'.orders }}'],
                        ['users', '{{ '+responseStatusHandle.reference_name+'.users }}']
                    ];

                    if( index > 2 ){
                        return examples[0][value];
                    }else{
                        return examples[index][value];
                    }

                }else{
                    
                    var examples = [
                        ['error_message', '{{ '+responseStatusHandle.reference_name+'.error.message }}']
                    ];

                    return examples[0][value];

                }

                return '';
            },
            getErrorOrSuccessClass(responseStatusHandle){
                return this.isGoodStatus(responseStatusHandle.status) ? 'text-success' : 'text-danger';

            },
            getCustomMessagePlaceholder(responseStatusHandle){
                var goodMsg = 'Hey {{ first_name }}, thank you for signing up!';
                var badMsg = 'Sorry {{ first_name }}, we are experiencing technical difficulties';
                return this.isGoodStatus(responseStatusHandle.status) ? goodMsg : badMsg;
            },
            handleTabRemoved(statusType){

                var response_status_handles = this.event.event_data.response.manual.response_status_handles;
                
                for(var x=0; x < response_status_handles.length; x++){

                    if( response_status_handles[x].status == statusType){

                        //  Remove the response status handle 
                        this.event.event_data.response.manual.response_status_handles.splice(x, 1);

                        break;

                    }

                }

                this.activeNavTab = this.getFirstTabStatus();

                this.forceRenderTabs();

            },
            forceRenderTabs(){
                this.renderKey = ++this.renderKey;
            },
            addStatusCodeHandle(){
                this.isOpenAddStatusCodeHandle = true;
            },
            getFirstTabStatus(){

                if( this.responseStatusHandlesExist ){

                    return this.event.event_data.response.manual.response_status_handles[0].status;

                }

                return null;
            },
            handleCreatedStatusHandle( statusCodeHandle ){

                //  Set the active navigation tab to the current status code handle
                this.activeNavTab = statusCodeHandle.status;

            },
            addResponseOption(responseStatusHandle){
                
                //  Build the option template
                var template = {
                        name: '',
                        value: ''
                    };

                //  Add the template to the current response status handle attributes
                responseStatusHandle.attributes.push( template );

            },
            removeResponseOption(responseStatusHandle, index){

                //  Remove option 
                responseStatusHandle.attributes.splice(index, 1);

            }

        }
    };
  
</script>