<template>

    <List slot="content" size="small">

        <!-- Active State -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Active: </span>
            <span v-if="event.active.code_editor_mode">

                <Icon type="ios-code" class="mr-1" size="20" />
                <span>Custom Code</span>

            </span>
            <template v-else>
                <span class="cut-text">{{ event.active.text ? 'Yes' : 'No' }}</span>
            </template>
        </ListItem>
        
        <!-- Name -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Name: </span>
            <span class="cut-text">{{ event.name }}</span>
        </ListItem>

        <!-- API Method -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Method: </span>
            <span v-if="event.event_data.method" class="cut-text text-uppercase">{{ event.event_data.method }}</span>
            <span v-else class="text-danger">No API Method</span>
        </ListItem>

        <!-- API URL -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Url: </span>
            <span v-if="event.event_data.url.code_editor_mode">

                <Icon type="ios-code" class="mr-1" size="20" />
                <span>Custom Code</span>

            </span>
            <template v-else>
                <span v-if="event.event_data.url.text" v-html="event.event_data.url.text" class="cut-text"></span>
                <span v-else class="text-danger">No API Url</span>
            </template>
        </ListItem>

        <!-- Query Params -->
        <ListItem v-if="numberOfQueryParams" class="p-0">
            <span class="font-weight-bold mr-1">Query Params: </span>
            <span class="cut-text">{{ numberOfQueryParams }} found</span>
        </ListItem>

        <!-- Form Data -->
        <ListItem v-if="numberOfFormData" class="p-0">
            <span class="font-weight-bold mr-1">Form Data: </span>
            <span class="cut-text">{{ numberOfFormData }} found</span>
        </ListItem>

        <!-- Headers -->
        <ListItem v-if="numberOfHeaders" class="p-0">
            <span class="font-weight-bold mr-1">Headers: </span>
            <span class="cut-text">{{ numberOfHeaders }} found</span>
        </ListItem>

        <!-- On Handle Response Manually -->
        <template v-if="event.event_data.response.selected_type == 'manual'">
            

            <!-- Response Status Handles (Good statuses e.g 200 - 300) -->
            <ListItem class="p-0">
                <span class="font-weight-bold mr-1">Success Handles: </span>
                <div>
                    <Tag v-for="(handle, index) in successResponseStatusHandles" :key="index" 
                         type="border" color="success" class="text-wrap">
                        {{ handle.status }}
                    </Tag>
                </div>
            </ListItem>

            <!-- Response Status Handles (Bad statuses e.g 400 - 500) -->
            <ListItem class="p-0">
                <span class="font-weight-bold mr-1">Error Handles: </span>
                <div>
                    <Tag v-for="(handle, index) in errorResponseStatusHandles" :key="index" 
                        type="border" color="error" class="text-wrap">
                        {{ handle.status }}
                    </Tag>
                </div>
            </ListItem>
            
        </template>

        <!-- On Handle Response Automatically -->
        <template v-else>

            <!-- Automatic Success Handle Type -->
            <ListItem class="p-0">
                <span class="font-weight-bold mr-1">On Success: </span>
                <span class="cut-text">{{ automaticSuccessResponse }}</span>
            </ListItem>

            <!-- Automatic Success Handle Type -->
            <ListItem class="p-0">
                <span class="font-weight-bold mr-1">On Fail: </span>
                <span class="cut-text">{{ automaticErrorResponse }}</span>
            </ListItem>

        </template>

        <ListItem v-if="event.comment" class="list-item-comment">
            <span>
                <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                <span class="font-weight-bold">Comment: </span><br>{{ event.comment }}
            </span>
        </ListItem>
    
    </List>

</template>

<script>

    var requestResponseCustomMixin = require('./../../edit-event/apis/crud-api/request-response/localMixin.js').default;

    export default {
        mixins: [requestResponseCustomMixin],
        props: {
            event: {
                type: Object,
                default:() => {}
            }
        },
        data(){
            return {
                
            }
        },
        watch: {

            //  Watch for changes on the isValidEvent
            isValidEvent: {
                handler: function (val, oldVal) {

                    //  Emit the isValidEvent the parent value
                    this.emitIsValidEvent();

                }
            }
        },
        computed: {
            isValidEvent(){
                /** This event is invalid if:
                 * 
                 *  1) We are not using code editor mode and the Api Url is not provided 
                 *  2) The Api Method is not provided
                 *  
                 */
                if( !this.event.event_data.url.code_editor_mode && !this.event.event_data.url.text ||
                    !this.event.event_data.method){

                    return false;

                }

                return true;
            },
            numberOfQueryParams(){
                return this.event.event_data.query_params.length
            },
            numberOfFormData(){
                return this.event.event_data.form_data_params.length
            },
            numberOfHeaders(){
                return this.event.event_data.headers.length
            },
            automaticSuccessResponse(){
                //  Note: "automaticSuccessOptions" is defind within "requestResponseCustomMixin"
                for (let index = 0; index < this.automaticSuccessOptions.length; index++) {
                    
                    if( this.automaticSuccessOptions[index]['value'] == this.event.event_data.response.automatic.on_handle_success ){
                        
                        return this.automaticSuccessOptions[index]['name'];

                    }
                    
                }
            },
            automaticErrorResponse(){
                //  Note: "automaticErrorOptions" is defind within "requestResponseCustomMixin"
                for (let index = 0; index < this.automaticErrorOptions.length; index++) {
                    
                    if( this.automaticErrorOptions[index]['value'] == this.event.event_data.response.automatic.on_handle_error ){
                        
                        return this.automaticErrorOptions[index]['name'];

                    }
                    
                }
            },
            successResponseStatusHandles(){
                
                const self = this;

                return this.event.event_data.response.manual.response_status_handles.filter(function(responseStatusHandle){
                    
                    //  Note: "isGoodStatus()" is defind within "requestResponseCustomMixin"
                    return self.isGoodStatus( responseStatusHandle.status );

                });
            },
            errorResponseStatusHandles(){

                const self = this;

                return this.event.event_data.response.manual.response_status_handles.filter(function(responseStatusHandle){
                    
                    //  Note: "isGoodStatus()" is defind within "requestResponseCustomMixin"
                    return !self.isGoodStatus( responseStatusHandle.status );

                });
            }
        },
        methods: {
            emitIsValidEvent(){

                //  Update the parent value
                this.$emit('updateIsValidEvent', this.isValidEvent);

            }
        },
        created(){
            
            //  Emit the isValidEvent the parent value
            this.emitIsValidEvent();

        }
    }

</script>
