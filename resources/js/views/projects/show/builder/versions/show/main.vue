<template>

    <Row>

        <Col v-if="isLoading" :span="24">

            <!-- Show Loader -->
            <Loader class="mt-3"></Loader>

        </Col>
        
        <template v-else>

            <Col :span="24">

                <!-- Show version editor or simulator -->
                <Tabs v-model="activeNavTab" name="builder-tabs" class="builder-main-tabs" style="overflow: visible;" :animated="false">

                    <!-- Screen Settings Navigation Tabs -->
                    <TabPane v-for="(currentTabName, key) in navTabs" :key="key" 
                             :label="currentTabName.name" :name="currentTabName.value" :icon="currentTabName.icon">
                    </TabPane>

                </Tabs>

                <editor v-show="activeNavTab == 1" :project="project" :version="version" class="p-2"></editor>

                <simulator v-show="activeNavTab == 2" :project="project" :version="version" class="p-2"></simulator>

            </Col>

        </template>

    </Row>

</template>

<script>

    import editor from './editor/main.vue';
    import simulator from './simulator/main.vue';
    import Loader from './../../../../../../components/_common/loaders/default.vue';

    export default {
        components: { editor, simulator, Loader },
        props: {
            project: {
                type: Object,
                default: null
            },
            requestToSaveChanges: {
                type: Number,
                default: 0
            }
        },
        data(){
            return {
                version: null,
                versionBeforeChanges: null,
                isLoading: false,
                isSaving: false,
                activeNavTab: '1',
                navTabs: [
                    {
                        icon: 'ios-git-branch',
                        name: 'Editor',
                        value: '1',
                    },
                    {
                        icon: 'ios-phone-portrait',
                        name: 'Simulator',
                        value: '2'
                    }
                ]
            }
        },
        watch: {
            /*  Keep track of changes on the version  */
            version: {

                handler: function (val, oldVal) {

                    this.notifyUnsavedChangesStatus();

                },
                deep: true

            },
            /** Watch to see if we want to save changes.
             *  If we do handle the request.
             */
            requestToSaveChanges(newVal, oldVal){

                this.saveVersion();
            }
        },
        computed: {
            versionUrl(){
                return decodeURIComponent(this.$route.params.version_url);
            }
        },
        methods: {
            copyVersionBeforeUpdate(){
                
                //  Clone the version
                this.versionBeforeChanges = _.cloneDeep( this.version );

            },
            versionHasBeenUpdated(){

                //  Check if the version has been modified
                return !_.isEqual(this.version, this.versionBeforeChanges);

            },
            notifyUnsavedChangesStatus(){

                //  Notify the parent if we have changes to save
                this.$emit('unsavedChanges', this.versionHasBeenUpdated());

            },
            fetchVersion() {

                //  If we have the version url
                if( this.versionUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isLoading = true;

                    //  Use the api call() function, refer to api.js
                    api.call('get', this.versionUrl)
                        .then(({data}) => {
                            
                            //  Console log the data returned
                            console.log(data);

                            //  Get the version
                            self.version = data || null;
                
                            //  Copy the version before any chages are made
                            self.copyVersionBeforeUpdate();

                            //  Stop loader
                            self.isLoading = false;

                            self.$emit('loadedVersion', self.version)

                        })         
                        .catch(response => { 

                            //  Log the responce
                            console.error(response);

                            //  Stop loader
                            self.isLoading = false;

                        });
                }
            },
            saveVersion() {

                //  If we have the version url
                if( this.versionUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isSaving = true;

                    this.$emit('isSaving', self.isSaving);

                    var versionPayload = this.version;

                    //  Use the api call() function, refer to api.js
                    api.call('put', this.versionUrl, versionPayload)
                        .then(({data}) => {
                            
                            //  Console log the data returned
                            console.log(data);
                            
                            self.copyVersionBeforeUpdate();

                            self.notifyUnsavedChangesStatus();

                            self.$Message.success({
                                content: 'Project saved!',
                                duration: 6
                            });

                            //  Stop loader
                            self.isSaving = false;

                            self.$emit('isSaving', self.isSaving);

                        })         
                        .catch(response => { 

                            //  Log the responce
                            console.error(response);

                            //  Stop loader
                            self.isSaving = false;

                            self.$emit('isSaving', self.isSaving);

                        });
                }
            }
        },
        created(){

            //  Fetch the project
            this.fetchVersion();
            
        }
    }
</script>
