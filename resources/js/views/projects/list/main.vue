<template>

    <Row :gutter="12">
        
        <Col :span="20" :offset="2">

            <Row :gutter="12">

                <Col :span="24">

                    <h1 class="text-center border-bottom-dashed py-3 mb-3">My Projects</h1>

                </Col>

                <Col :span="8">

                    <Card class="add-sce-mini-card-button mb-3"
                          @click.native="navigateToCreateProject()">
                        <div class="action-title">
                            <Icon type="ios-add" />
                            <span>Add Project</span>
                        </div>
                    </Card>

                    <singleProjectCard v-for="(project, index) in firstColumnProjects" :key="index" :index="index" :project="project"></singleProjectCard>

                </Col>

                <Col :span="8">

                    <singleProjectCard v-for="(project, index) in secondColumnProjects" :key="index" :index="index" :project="project"></singleProjectCard>

                </Col>

                <Col :span="8">

                    <singleProjectCard v-for="(project, index) in thirdColumnProjects" :key="index" :index="index" :project="project"></singleProjectCard>

                </Col>

            </Row>

        </Col>

    </Row>

</template>

<script>
    
    import singleProjectCard from './components/singleProjectCard.vue'; 

    export default {
        components: { singleProjectCard },
        data(){
            return {
                user: auth.getUser(),
                projects: []
            }
        },

        computed: {
            projectsUrl(){
                return this.user['_links']['sce:projects'].href;
            },
            firstColumnProjects(){
                return this.projects.filter((project, index) => {
                    var position = (index + 1);
                    if( (position) == 3  || (position % 3) == 0 ){
                        return true;
                    }
                })
            },
            secondColumnProjects(){
                return this.projects.filter((project, index) => {
                    var position = (index + 1);
                    if( (position) == 1  || (position % 3) == 1 ){
                        return true;
                    }
                })
            },
            thirdColumnProjects(){
                return this.projects.filter((project, index) => {
                    var position = (index + 1);
                    if( (position) == 2 || (position % 3) == 2 ){
                        return true;
                    }
                })
            }
        },
        methods: {
            navigateToCreateProject(){
                
                //  Navigate to create new project
                this.$router.push({ name: 'create-project' });
                
            },
            fetchProjects() {

                //  If we have the project url
                if( this.projectsUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isLoading = true;

                    console.log('Fetch projects');

                    //  Use the api call() function, refer to api.js
                    api.call('get', this.projectsUrl)
                        .then(({data}) => {

                            //  Get the projects
                            self.projects = ((data || [])['_embedded'] || [])['projects'];

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

            //  Fetch the project
            this.fetchProjects();
            
        }
    }
</script>
