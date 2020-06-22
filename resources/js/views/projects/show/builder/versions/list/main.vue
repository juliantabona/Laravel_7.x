<template>

    <Row :gutter="12">
        
        <Col :span="20" :offset="2">

            <Row :gutter="12">

                <Col :span="24" class="clearfix">
            
                    <Button type="default" size="large" class="mt-3 mb-3" @click.native="navigateToProject()">
                        <Icon type="md-arrow-back" class="mr-1" :size="20" />
                        <span>Back</span>
                    </Button>
                
                </Col>

                <Col :span="8">

                    <Card class="add-sce-mini-card-button mb-3"
                          @click.native="navigateToCreateVersion()">
                        <div class="action-title">
                            <Icon type="ios-add" />
                            <span>Add Version</span>
                        </div>
                    </Card>

                    <singleVersionCard v-for="(version, index) in firstColumnVersions" :key="index" :index="index" 
                                       :project="project" :version="version" @selected="manageSelection($event)">
                    </singleVersionCard>

                </Col>

                <Col :span="8">

                    <singleVersionCard v-for="(version, index) in secondColumnVersions" :key="index" :index="index" 
                                       :project="project" :version="version" @selected="manageSelection($event)">
                    </singleVersionCard>

                </Col>

                <Col :span="8">

                    <singleVersionCard v-for="(version, index) in thirdColumnVersions" :key="index" :index="index" 
                                       :project="project" :version="version" @selected="manageSelection($event)">
                    </singleVersionCard>

                </Col>

            </Row>

        </Col>

    </Row>

</template>

<script>
    
    import singleVersionCard from './components/singleVersionCard.vue'; 

    export default {
        props: {
            project: {
                type: Object,
                default: null
            }
        },
        components: { singleVersionCard },
        data(){
            return {
                versions: []
            }
        },
        computed: {
            versionsUrl(){
                return this.project['_links']['sce:versions'].href;
            },
            firstColumnVersions(){
                return this.versions.filter((version, index) => {
                    var position = (index + 1);
                    if( (position) == 3  || (position % 3) == 0 ){
                        return true;
                    }
                })
            },
            secondColumnVersions(){
                return this.versions.filter((version, index) => {
                    var position = (index + 1);
                    if( (position) == 1  || (position % 3) == 1 ){
                        return true;
                    }
                })
            },
            thirdColumnVersions(){
                return this.versions.filter((version, index) => {
                    var position = (index + 1);
                    if( (position) == 2 || (position % 3) == 2 ){
                        return true;
                    }
                })
            }
        },
        methods: {
            navigateToProject(){
                /** Note that using router.push() or router.replace() does not allow us to make a
                 *  page refresh when visiting routes. This is undesirable at this moment since our 
                 *  parent component contains the <router-view />. When the page does not refresh, 
                 *  the <router-view /> is not able to receice the nested components defined in the 
                 *  route.js file. This means that we are then not able to render the nested 
                 *  components and present them. To counter this issue we must construct the 
                 *  href and use "window.location.href" to make a hard page refresh.
                 */
                var projectUrl = this.project['_links']['self'].href;
                //  Add the "menu" query to our current project route
                var route = { name: 'show-project-builder', params: { project_url: encodeURIComponent(projectUrl) } };
                //  Contruct the full path url
                var href = window.location.origin + "/" + VueInstance.$router.resolve(route).href
                //  Visit the url
                window.location.href = href;
            },
            manageSelection(version){
                this.$emit('selected', version);
            },
            navigateToCreateVersion(){
                
                //  Navigate to create new version
                this.$router.push({ name: 'create-project-version', param: { project_url: 'Project URL' } });
                
            },
            fetchVersions() {

                //  If we have the version url
                if( this.versionsUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isLoading = true;

                    console.log('Fetch versions');

                    //  Use the api call() function, refer to api.js
                    api.call('get', this.versionsUrl)
                        .then(({data}) => {

                            //  Get the versions
                            self.versions = ((data || [])['_embedded'] || [])['versions'];

                            //  Stop loader
                            self.isLoading = false;

                        })         
                        .catch(response => { 

                            //  Log the responce
                            console.error(response);

                            //  Stop loader
                            self.isLoading = false;

                        });
                }
            }
        },
        created(){

            //  Fetch the version
            this.fetchVersions();
            
        }
    }
</script>
