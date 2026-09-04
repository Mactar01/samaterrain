import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class DioClient {
  static final DioClient _instance = DioClient._internal();
  late final Dio dio;

  factory DioClient() {
    return _instance;
  }

  DioClient._internal() {
    // URL locale pour les tests sur réseau WiFi partagé
    // Le téléphone et le PC doivent être sur le même WiFi
    // const String baseUrl = 'http://192.168.7.140:8000/api/v1'; // Local
    const String baseUrl = 'https://releases-jar-perth-sage.trycloudflare.com/api/v1'; // Cloudflare Tunnel (Très stable)

    dio = Dio(BaseOptions(
      baseUrl: baseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 10),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ));

    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final prefs = await SharedPreferences.getInstance();
          final token = prefs.getString('auth_token');
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (error, handler) async {
          if (error.response?.statusCode == 401) {
            final prefs = await SharedPreferences.getInstance();
            await prefs.remove('auth_token');
            // TODO: Rediriger vers le login via un event global ou callback
          }
          return handler.next(error);
        },
      ),
    );
  }
}
