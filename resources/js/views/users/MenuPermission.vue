<template>
    <div class="content">
        <div class="container-fluid">
            <breadcrumb :options="['User Menu Permission']"></breadcrumb>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body">

                            <div class="row">
                                <div class="col-4">
                                    <form class="form-horizontal" @submit.prevent="getUserMenu">
                                            <div class="form-group">
                                                <label>User</label>
                                                <div class="input-group">
                                                    <select class="form-control" id="user" v-model="userId" name="userId">
                                                        <option disabled value="">Select User</option>
                                                        <option :value="user.user_id" v-for="(user , index) in users" :key="index">{{ user.name }}</option>
                                                    </select>
                                                    <div class="form-group row mb-0" >
                                                        <div class="col-sm-12 text-right">
                                                            <button type="submit" class="btn btn-success">Search</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    </form>
                                </div>
                                <div class="col-12">
                                    <menu-tree-view v-if="isLoading" :treeList="treeList" :userId="userId" :permission="permission"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
document.title = 'User Menu Permission | Medical Bill';
import {baseurl} from '../../base_url'
export default {
    name: "Create",
    data() {
        return {
            users: [],
            userId: '',
            treeList: [],
            permission: [],
            isLoading: false
        }
    },
    mounted() {
        this.getAllUser();
    },
    methods: {
        getUserMenu() {
            this.isLoading = false;
            this.permission = [];
            axios.get(baseurl + `api/get-user-menu-details/${this.userId}`).then((response)=>{
                console.log(response.data)
                this.treeList = response.data.data.menu;
                response.data.data.usermenu.forEach((item) => {
                    this.permission[item] = true;
                })
                this.isLoading = true;
            }).catch((error)=>{

            })
        },
        checkboxData(menuId) {
            if (this.form.menu_id.includes(menuId)) {
                this.form.menu_id = this.form.menu_id.filter((item) => {
                    return item !== menuId
                });
            } else {
                this.form.menu_id.push(menuId);
            }
        },
        getAllUser() {
            this.isLoading = true;
            axios.get(baseurl + 'api/get-all-users').then((response) => {
                this.users = response.data.data;
                this.isLoading = false;
            }).catch((error) => {

            })
        },
        getAllMenu() {
            this.isLoading = true;
            axios.get(baseurl + 'api/get-all-menu').then((response) => {
                this.menus = response.data.data;
                this.menus = this.menus.map((item) => {
                    item.status = false;
                    return item;
                })
                this.isLoading = false;
            }).catch((error) => {

            })
        },
        addUserMenuPermission() {
            this.form.post(baseurl + "api/menu-permission-store").then(response => {
                this.$toaster.success('Data Successfully Updated');
            }).catch(e => {
                this.isLoading = false;
            });
        },
    }
}
</script>

<style scoped>

</style>
