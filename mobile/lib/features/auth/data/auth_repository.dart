import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/network/dio_client.dart';

class AuthRepository {
  final Dio dio = DioClient().dio;

  Future<String?> login(String email, String password) async {
    try {
      final response = await dio.post('/auth/login', data: {
        'email': email,
        'password': password,
      });

      if (response.statusCode == 200) {
        final token = response.data['token'];
        final role = response.data['user']['role'];
        
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', token);
        await prefs.setString('user_role', role);
        return role; // Return role instead of bool
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  Future<String?> register(String name, String email, String phone, String password, String passwordConfirmation) async {
    try {
      final response = await dio.post('/auth/register', data: {
        'name': name,
        'email': email,
        'phone': phone,
        'password': password,
        'password_confirmation': passwordConfirmation,
      });

      if (response.statusCode == 201) {
        final token = response.data['token'];
        final role = response.data['user']['role'];
        
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', token);
        await prefs.setString('user_role', role);
        return role;
      }
      return null;
    } on DioException catch (e) {
      if (e.response?.data != null && e.response!.data['message'] != null) {
        throw Exception(e.response!.data['message']);
      }
      throw Exception("Erreur lors de l'inscription.");
    } catch (e) {
      throw Exception("Erreur inattendue.");
    }
  }

  Future<void> logout() async {
    try {
      await dio.post('/auth/logout');
    } catch (e) {}
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }
}
