import {baseurl} from '../base_url'
export default{
    submitButtonLoadingStatus({commit},payload){
        console.log(payload)
    },
    getAllUserMenu(context){
        axios.get(baseurl + 'api/sidebar-get-all-user-menu').then((response)=>{
            context.commit('getAllUserMenu',response.data.user_menu);
        }).catch((error)=>{
            if(error.response.data.status == 401){
                localStorage.removeItem('token');
                window.location.href = '/medical-billing/login';
            }
        })
    }
}
